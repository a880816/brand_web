<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, Page, Service, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function context(): array
    {
        $a=Brand::factory()->create(['slug'=>'verdant','domain'=>'brand-a.localhost']);
        $b=Brand::factory()->create(['slug'=>'terracotta','domain'=>'brand-b.localhost']);
        $admin=User::factory()->brandAdmin()->create();
        $admin->brands()->attach($a);
        return [$a,$b,$admin];
    }

    private function asHost(User $user)
    {
        return $this->actingAs($user)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost']);
    }

    public function test_page_service_and_course_crud_always_use_server_brand_context(): void
    {
        [$a,$b,$admin]=$this->context();
        $this->asHost($admin)->post('/admin/pages',['brand_id'=>$b->id,'title'=>'品牌頁面','slug'=>'brand-page','type'=>'custom','status'=>'draft'])->assertRedirect();
        $this->asHost($admin)->post('/admin/services',['brand_id'=>$b->id,'name'=>'品牌服務','slug'=>'brand-service','summary'=>'摘要','status'=>'draft','sort_order'=>1])->assertRedirect();
        $this->asHost($admin)->post('/admin/courses',['brand_id'=>$b->id,'name'=>'品牌課程','slug'=>'brand-course','summary'=>'摘要','status'=>'draft','sort_order'=>1])->assertRedirect();
        $this->assertDatabaseHas('pages',['brand_id'=>$a->id,'slug'=>'brand-page']);
        $this->assertDatabaseHas('services',['brand_id'=>$a->id,'slug'=>'brand-service']);
        $this->assertDatabaseHas('courses',['brand_id'=>$a->id,'slug'=>'brand-course']);
        $this->assertDatabaseMissing('pages',['brand_id'=>$b->id,'slug'=>'brand-page']);
    }

    public function test_publish_unpublish_and_preview_permissions(): void
    {
        [$a,$b,$admin]=$this->context();
        $page=Page::factory()->create(['brand_id'=>$a->id,'type'=>'custom','status'=>'draft','published_at'=>null]);
        $foreign=Page::factory()->create(['brand_id'=>$b->id,'type'=>'custom','status'=>'draft']);
        $this->asHost($admin)->get('/admin/pages/'.$page->id.'/preview')->assertOk()->assertSee('草稿預覽');
        $this->asHost($admin)->get('/admin/pages/'.$foreign->id.'/preview')->assertNotFound();
        $this->asHost($admin)->post('/admin/pages/'.$page->id.'/publish')->assertRedirect();
        $this->assertSame('published',$page->fresh()->status);
        $this->assertNotNull($page->fresh()->published_at);
        $this->asHost($admin)->post('/admin/pages/'.$page->id.'/unpublish')->assertRedirect();
        $this->assertSame('draft',$page->fresh()->status);
    }

    public function test_brand_link_rejects_non_http_url_and_cross_brand_record(): void
    {
        [$a,$b,$admin]=$this->context();
        $this->asHost($admin)->post('/admin/links',['type'=>'website','label'=>'危險連結','url'=>'javascript:alert(1)','sort_order'=>0])->assertSessionHasErrors('url');
        $foreign=$b->allLinks()->create(['type'=>'website','label'=>'B','url'=>'https://example.com','sort_order'=>0,'is_enabled'=>true]);
        $this->asHost($admin)->put('/admin/links/'.$foreign->id,['type'=>'website','label'=>'變更','url'=>'https://example.org','sort_order'=>0,'is_enabled'=>1])->assertNotFound();
    }

    public function test_brand_admin_cannot_change_slug_domain_or_inject_theme_keys(): void
    {
        [$a,,$admin]=$this->context();
        $payload=['name'=>'更新名稱','slug'=>'hacked','domain'=>'evil.test','status'=>'inactive','primary_color'=>'#112233','secondary_color'=>'#223344','accent_color'=>'#334455','background_color'=>'#445566','text_color'=>'#556677','eyebrow'=>'SAFE','hero_title'=>'Hero','hero_note'=>'Note','theme_settings'=>['script'=>'alert(1)']];
        $this->asHost($admin)->put('/admin/settings',$payload)->assertRedirect();
        $brand=$a->fresh();
        $this->assertSame('verdant',$brand->slug);
        $this->assertSame('brand-a.localhost',$brand->domain);
        $this->assertSame('active',$brand->status);
        $this->assertArrayNotHasKey('script',$brand->theme_settings);
    }

    public function test_profile_password_requires_current_password_and_updates_hash(): void
    {
        [$a,,$admin]=$this->context();
        $admin->update(['password'=>Hash::make('OldPassword!')]);
        $this->asHost($admin)->put('/admin/profile/password',['current_password'=>'wrong','password'=>'NewPassword!','password_confirmation'=>'NewPassword!'])->assertSessionHasErrors('current_password');
        $this->asHost($admin)->put('/admin/profile/password',['current_password'=>'OldPassword!','password'=>'NewPassword!','password_confirmation'=>'NewPassword!'])->assertRedirect();
        $this->assertTrue(Hash::check('NewPassword!',$admin->fresh()->password));
    }

    public function test_frontend_excludes_future_and_draft_records_for_all_content_types(): void
    {
        [$a,,$admin]=$this->context();
        Page::factory()->create(['brand_id'=>$a->id,'type'=>'home','slug'=>'home']);
        Service::factory()->create(['brand_id'=>$a->id,'name'=>'Draft service','status'=>'draft']);
        Course::factory()->create(['brand_id'=>$a->id,'name'=>'Future course','published_at'=>now()->addHour()]);
        $this->get('http://brand-a.localhost/services')->assertDontSee('Draft service');
        $this->get('http://brand-a.localhost/courses')->assertDontSee('Future course');
    }
}
