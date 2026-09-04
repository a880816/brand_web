<?php

namespace Tests\Feature;

use App\Livewire\Admin\PageSectionEditor;
use App\Models\{AuditLog, Brand, Media, Page, PageSection, User};
use App\Services\{AuditService, MediaService};
use App\Support\BrandContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Tests\TestCase;

class PageSectionAndMediaTest extends TestCase
{
    use RefreshDatabase;

    private function setupBrand(string $slug='verdant', string $domain='brand-a.localhost'): array
    {
        $brand = Brand::factory()->create(['slug'=>$slug,'domain'=>$domain]);
        $page = Page::factory()->create(['brand_id'=>$brand->id,'type'=>'home','slug'=>'home']);
        return [$brand,$page];
    }

    public function test_each_brand_renders_only_its_own_active_sections_and_swiper(): void
    {
        [$a,$pageA] = $this->setupBrand();
        [$b,$pageB] = $this->setupBrand('terracotta','brand-b.localhost');
        $gallery = PageSection::factory()->create(['brand_id'=>$a->id,'page_id'=>$pageA->id,'type'=>'gallery','heading'=>'A 品牌輪播']);
        Media::factory()->create(['brand_id'=>$a->id,'mediable_type'=>$gallery->getMorphClass(),'mediable_id'=>$gallery->id]);
        PageSection::factory()->create(['brand_id'=>$b->id,'page_id'=>$pageB->id,'heading'=>'B 品牌限定']);
        PageSection::factory()->create(['brand_id'=>$a->id,'page_id'=>$pageA->id,'heading'=>'隱藏內容','status'=>'inactive']);
        $this->get('http://brand-a.localhost/')->assertOk()->assertSee('A 品牌輪播')->assertSee('js-swiper')->assertDontSee('B 品牌限定')->assertDontSee('隱藏內容');
    }

    public function test_livewire_updates_reapply_brand_and_account_middleware(): void
    {
        $middleware = app(PersistentMiddleware::class)->getPersistentMiddleware();
        $this->assertContains(\App\Http\Middleware\ResolveBrand::class,$middleware);
        $this->assertContains(\App\Http\Middleware\EnsureAccountActive::class,$middleware);
        $this->assertContains(\App\Http\Middleware\ResolveAdminBrand::class,$middleware);
    }

    public function test_livewire_payload_cannot_read_or_edit_another_brand_section(): void
    {
        [$a,$pageA] = $this->setupBrand();
        [$b,$pageB] = $this->setupBrand('terracotta','brand-b.localhost');
        $foreign = PageSection::factory()->create(['brand_id'=>$b->id,'page_id'=>$pageB->id]);
        $admin = User::factory()->brandAdmin()->create();
        $admin->brands()->attach($a);
        app(BrandContext::class)->set($a);
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        Livewire::actingAs($admin)->test(PageSectionEditor::class,['pageId'=>$pageA->id])->call('edit',$foreign->id);
    }

    public function test_section_editor_uses_type_and_url_allowlists(): void
    {
        [$brand,$page] = $this->setupBrand();
        $admin = User::factory()->brandAdmin()->create();
        $admin->brands()->attach($brand);
        app(BrandContext::class)->set($brand);
        Livewire::actingAs($admin)->test(PageSectionEditor::class,['pageId'=>$page->id])->set('type','blade_path')->set('buttonUrl','javascript:alert(1)')->call('save')->assertHasErrors(['type','buttonUrl']);
        $this->assertDatabaseCount('page_sections',0);
    }

    public function test_media_creates_webp_variants_without_upscaling_and_deletes_all_files(): void
    {
        Storage::fake('public');
        [$brand,$page] = $this->setupBrand();
        app(BrandContext::class)->set($brand);
        $media = app(MediaService::class)->store($page,UploadedFile::fake()->image('plant.jpg',800,600),'gallery',['is_primary'=>true]);
        Storage::disk('public')->assertExists($media->path);
        $this->assertSame(['thumbnail','card','detail'],array_keys($media->variants));
        foreach ($media->variants as $path) Storage::disk('public')->assertExists($path);
        $this->assertSame(800,$media->width);
        app(MediaService::class)->delete($media);
        Storage::disk('public')->assertMissing($media->path);
        foreach ($media->variants as $path) Storage::disk('public')->assertMissing($path);
    }

    public function test_media_rejects_forged_image_and_invalid_collection(): void
    {
        Storage::fake('public');
        [$brand,$page] = $this->setupBrand();
        app(BrandContext::class)->set($brand);
        try { app(MediaService::class)->store($page,UploadedFile::fake()->createWithContent('fake.jpg','not-an-image'),'gallery'); $this->fail('Forged image was accepted.'); } catch (ValidationException) { $this->assertTrue(true); }
        $this->expectException(ValidationException::class);
        app(MediaService::class)->store($page,UploadedFile::fake()->image('plant.jpg'),'executable');
    }

    public function test_audit_log_excludes_sensitive_values(): void
    {
        [$brand] = $this->setupBrand();
        $user = User::factory()->create();
        $this->actingAs($user);
        app(AuditService::class)->record('security.test',$brand,['password'=>'before','token'=>'abc'],['password'=>'after','session_cookie'=>'not-used']);
        $log = AuditLog::firstOrFail();
        $this->assertArrayNotHasKey('password',$log->before ?? []);
        $this->assertArrayNotHasKey('token',$log->before ?? []);
        $this->assertArrayNotHasKey('password',$log->after ?? []);
        $this->assertArrayNotHasKey('session_cookie',$log->after ?? []);
    }
}
