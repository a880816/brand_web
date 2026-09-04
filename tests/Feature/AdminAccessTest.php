<?php

namespace Tests\Feature;

use App\Models\{Brand, Page, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Support\Facades\{Hash, RateLimiter};
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function brand(string $slug='verdant', string $domain='brand-a.localhost'): Brand
    {
        return Brand::factory()->create(['slug'=>$slug,'domain'=>$domain]);
    }

    private function host() { return $this->withServerVariables(['HTTP_HOST'=>'brand-a.localhost']); }
    private function postCsrf(string $uri, array $data=[]) { return $this->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->post($uri,$data); }
    private function putCsrf(string $uri, array $data=[]) { return $this->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->put($uri,$data); }

    public function test_login_logout_and_last_login_are_recorded(): void
    {
        $this->brand();
        $user = User::factory()->superAdmin()->create(['email'=>'admin@example.test','password'=>Hash::make('CorrectPassword!')]);
        $this->postCsrf('/login',['email'=>$user->email,'password'=>'CorrectPassword!','remember'=>1])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->postCsrf('/logout')->assertRedirect('/login');
        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs',['action'=>'auth.login']);
        $this->assertDatabaseHas('audit_logs',['action'=>'auth.logout']);
    }

    public function test_wrong_password_disabled_account_and_rate_limit_are_rejected(): void
    {
        $this->brand();
        $active = User::factory()->create(['email'=>'active@example.test','password'=>Hash::make('CorrectPassword!')]);
        $disabled = User::factory()->disabled()->create(['email'=>'disabled@example.test','password'=>Hash::make('CorrectPassword!')]);
        $this->postCsrf('/login',['email'=>$active->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->postCsrf('/login',['email'=>$disabled->email,'password'=>'CorrectPassword!'])->assertSessionHasErrors('email');
        $key = strtolower($active->email).'|127.0.0.1';
        RateLimiter::clear($key);
        for ($i=0;$i<5;$i++) RateLimiter::hit($key,60);
        $this->postCsrf('/login',['email'=>$active->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_member_can_edit_profile_but_cannot_manage_content(): void
    {
        $this->brand();
        $member = User::factory()->create();
        $this->actingAs($member);
        $this->host()->get('/admin')->assertRedirect('/admin/profile');
        $this->host()->get('/admin/profile')->assertOk();
        $this->host()->get('/admin/pages')->assertForbidden();
    }

    public function test_brand_admin_is_restricted_to_assigned_brand_by_url_and_switch(): void
    {
        $a = $this->brand();
        $b = $this->brand('terracotta','brand-b.localhost');
        $pageA = Page::factory()->create(['brand_id'=>$a->id]);
        $pageB = Page::factory()->create(['brand_id'=>$b->id]);
        $admin = User::factory()->brandAdmin()->create();
        $admin->brands()->attach($a);
        $this->actingAs($admin);
        $this->host()->get('/admin/pages/'.$pageA->id.'/edit')->assertOk();
        $this->host()->get('/admin/pages/'.$pageB->id.'/edit')->assertNotFound();
        $this->postCsrf('/admin/switch-brand/'.$b->id)->assertForbidden();
    }

    public function test_super_admin_can_switch_brand_and_last_active_super_admin_is_protected(): void
    {
        $a = $this->brand();
        $b = $this->brand('terracotta','brand-b.localhost');
        $super = User::factory()->superAdmin()->create();
        $this->actingAs($super);
        $this->postCsrf('/admin/switch-brand/'.$b->id)->assertSessionHas('admin_brand_id',$b->id);
        $this->withSession(['admin_brand_id'=>$b->id])->host()->get('/admin')->assertOk()->assertSee($b->name);
        $this->putCsrf('/admin/users/'.$super->id,['name'=>$super->name,'email'=>$super->email,'password'=>'','password_confirmation'=>'','role'=>'member','status'=>'active'])->assertStatus(422);
    }

    public function test_existing_session_is_invalidated_when_user_is_disabled(): void
    {
        $this->brand();
        $user = User::factory()->create(['status'=>'disabled']);
        $this->actingAs($user);
        $this->host()->get('/admin/profile')->assertRedirect('/login');
        $this->assertGuest();
    }
}
