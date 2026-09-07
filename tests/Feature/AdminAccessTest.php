<?php

namespace Tests\Feature;

use App\Models\{Brand, Material, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{Hash, RateLimiter};
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void { parent::setUp(); $this->withoutMiddleware(ValidateCsrfToken::class); }
    private function brand(string $slug='verdant', string $domain='brand-a.localhost'): Brand { return Brand::factory()->create(compact('slug','domain')); }
    private function client() { return $this->withServerVariables(['HTTP_HOST'=>'brand-a.localhost']); }

    public function test_login_logout_and_last_login_are_recorded(): void
    {
        $this->brand();
        $user=User::factory()->superAdmin()->create(['email'=>'admin@example.test','password'=>Hash::make('CorrectPassword!')]);
        $this->client()->post('/login',['email'=>$user->email,'password'=>'CorrectPassword!','remember'=>1])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);$this->assertNotNull($user->fresh()->last_login_at);
        $this->client()->post('/logout')->assertRedirect('/login');$this->assertGuest();
        $this->assertDatabaseHas('audit_logs',['action'=>'auth.login']);$this->assertDatabaseHas('audit_logs',['action'=>'auth.logout']);
    }

    public function test_wrong_password_disabled_legacy_role_and_rate_limit_are_rejected(): void
    {
        $this->brand();
        $active=User::factory()->create(['email'=>'active@example.test','password'=>Hash::make('CorrectPassword!')]);
        $disabled=User::factory()->disabled()->create(['email'=>'disabled@example.test','password'=>Hash::make('CorrectPassword!')]);
        $legacy=User::factory()->create(['email'=>'member@example.test','role'=>'member','password'=>Hash::make('CorrectPassword!')]);
        $this->client()->post('/login',['email'=>$active->email,'password'=>'wrong'])->assertSessionHasErrors('email');
        $this->client()->post('/login',['email'=>$disabled->email,'password'=>'CorrectPassword!'])->assertSessionHasErrors('email');
        $this->client()->post('/login',['email'=>$legacy->email,'password'=>'CorrectPassword!'])->assertSessionHasErrors('email');
        $key=strtolower($active->email).'|127.0.0.1';RateLimiter::clear($key);for($i=0;$i<5;$i++)RateLimiter::hit($key,60);
        $this->client()->post('/login',['email'=>$active->email,'password'=>'wrong'])->assertSessionHasErrors('email');$this->assertGuest();
    }

    public function test_brand_admin_is_restricted_to_assigned_brand_by_url_and_switch(): void
    {
        $a=$this->brand();$b=$this->brand('terracotta','brand-b.localhost');
        $base=['product_code'=>'MAT','name'=>'資材','slug'=>'material','price'=>100,'stock_on_hand'=>1,'reserved_quantity'=>0,'low_stock_threshold'=>0,'status'=>'published','published_at'=>now()];
        $own=Material::create($base+['brand_id'=>$a->id]);$foreign=Material::create(array_merge($base,['brand_id'=>$b->id,'product_code'=>'OTHER','slug'=>'other']));
        $admin=User::factory()->brandAdmin()->create();$admin->brands()->attach($a);$this->actingAs($admin);
        $this->client()->get('/admin/materials/'.$own->id.'/edit')->assertOk();
        $this->client()->get('/admin/materials/'.$foreign->id.'/edit')->assertNotFound();
        $this->client()->post('/admin/switch-brand/'.$b->id)->assertForbidden();
    }

    public function test_super_admin_can_switch_brand_and_last_active_super_admin_is_protected(): void
    {
        $this->brand();$b=$this->brand('terracotta','brand-b.localhost');$super=User::factory()->superAdmin()->create();$this->actingAs($super);
        $this->client()->post('/admin/switch-brand/'.$b->id)->assertSessionHas('admin_brand_id',$b->id);
        $this->withSession(['admin_brand_id'=>$b->id])->client()->get('/admin')->assertOk()->assertSee($b->name);
        $this->client()->put('/admin/users/'.$super->id,['name'=>$super->name,'email'=>$super->email,'role'=>'brand_admin','status'=>'active'])->assertStatus(422);
    }

    public function test_disabled_existing_session_is_invalidated_and_non_admin_is_forbidden(): void
    {
        $this->brand();
        $disabled=User::factory()->create(['status'=>'disabled']);$this->actingAs($disabled);$this->client()->get('/admin/profile')->assertRedirect('/login');$this->assertGuest();
        $legacy=User::factory()->create(['role'=>'member']);$this->actingAs($legacy);$this->client()->get('/admin/profile')->assertForbidden();
    }
}
