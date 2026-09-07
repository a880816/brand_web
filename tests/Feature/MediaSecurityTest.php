<?php

namespace Tests\Feature;

use App\Livewire\Admin\MediaManager;
use App\Models\{AuditLog, Brand, HomepageContent, Media, User};
use App\Services\{AuditService, MediaService};
use App\Support\BrandContext;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Livewire\Mechanisms\PersistentMiddleware\PersistentMiddleware;
use Tests\TestCase;

class MediaSecurityTest extends TestCase
{
    use RefreshDatabase;
    private function fixture(string $slug='verdant',string $domain='brand-a.localhost'):array{$brand=Brand::factory()->create(compact('slug','domain'));$home=HomepageContent::create(['brand_id'=>$brand->id,'draft_data'=>[],'published_data'=>[]]);return[$brand,$home];}

    public function test_homepage_gallery_and_swiper_are_brand_isolated():void
    {
        [$a,$homeA]=$this->fixture();[$b,$homeB]=$this->fixture('terracotta','brand-b.localhost');
        $mediaA=Media::factory()->create(['brand_id'=>$a->id,'mediable_type'=>$homeA->getMorphClass(),'mediable_id'=>$homeA->id]);$mediaB=Media::factory()->create(['brand_id'=>$b->id,'mediable_type'=>$homeB->getMorphClass(),'mediable_id'=>$homeB->id]);
        $homeA->update(['published_data'=>['gallery_items'=>[['media_id'=>$mediaA->id,'title'=>'A 品牌輪播']]]]);$homeB->update(['published_data'=>['gallery_items'=>[['media_id'=>$mediaB->id,'title'=>'B 品牌限定']]]]);
        $this->get('http://brand-a.localhost/')->assertOk()->assertSee('A 品牌輪播')->assertSee('js-swiper')->assertDontSee('B 品牌限定');
    }

    public function test_livewire_reapplies_security_middleware_and_rejects_foreign_owner_payload():void
    {
        $middleware=app(PersistentMiddleware::class)->getPersistentMiddleware();foreach([\App\Http\Middleware\ResolveBrand::class,\App\Http\Middleware\EnsureAccountActive::class,\App\Http\Middleware\ResolveAdminBrand::class] as $item)$this->assertContains($item,$middleware);
        [$a]=$this->fixture();[$b,$foreign]=$this->fixture('terracotta','brand-b.localhost');$admin=User::factory()->brandAdmin()->create();$admin->brands()->attach($a);app(BrandContext::class)->set($a);
        $this->expectException(ModelNotFoundException::class);Livewire::actingAs($admin)->test(MediaManager::class,['ownerType'=>'homepage','ownerId'=>$foreign->id]);
    }

    public function test_media_variants_do_not_upscale_and_all_files_are_deleted():void
    {
        Storage::fake('public');[$brand,$home]=$this->fixture();app(BrandContext::class)->set($brand);$media=app(MediaService::class)->store($home,UploadedFile::fake()->image('plant.jpg',800,600),'gallery',['is_primary'=>true]);
        Storage::disk('public')->assertExists($media->path);$this->assertSame(['thumbnail','card','detail'],array_keys($media->variants));foreach($media->variants as $path)Storage::disk('public')->assertExists($path);$this->assertSame(800,$media->width);
        app(MediaService::class)->delete($media);Storage::disk('public')->assertMissing($media->path);foreach($media->variants as $path)Storage::disk('public')->assertMissing($path);
    }

    public function test_first_uploaded_image_becomes_primary_automatically():void
    {
        Storage::fake('public');[$brand,$home]=$this->fixture();app(BrandContext::class)->set($brand);
        $first=app(MediaService::class)->store($home,UploadedFile::fake()->image('first.jpg'),'gallery');
        $second=app(MediaService::class)->store($home,UploadedFile::fake()->image('second.jpg'),'gallery');
        $this->assertTrue($first->is_primary);$this->assertFalse($second->is_primary);$this->assertSame($first->id,$home->primaryMedia('gallery')->id);
    }

    public function test_media_rejects_forged_image_and_invalid_collection():void
    {
        Storage::fake('public');[$brand,$home]=$this->fixture();app(BrandContext::class)->set($brand);
        try{app(MediaService::class)->store($home,UploadedFile::fake()->createWithContent('fake.jpg','not-an-image'),'gallery');$this->fail('Forged image accepted');}catch(ValidationException){$this->assertTrue(true);}
        $this->expectException(ValidationException::class);app(MediaService::class)->store($home,UploadedFile::fake()->image('plant.jpg'),'executable');
    }

    public function test_audit_log_recursively_excludes_sensitive_values():void
    {
        [$brand]=$this->fixture();$this->actingAs(User::factory()->create());app(AuditService::class)->record('security.test',$brand,['password'=>'before','token'=>'abc'],['bank_snapshot'=>['bank_account_number'=>'123'],'session_cookie'=>'bad']);$log=AuditLog::firstOrFail();
        $this->assertArrayNotHasKey('password',$log->before??[]);$this->assertArrayNotHasKey('token',$log->before??[]);$this->assertArrayNotHasKey('bank_snapshot',$log->after??[]);$this->assertArrayNotHasKey('session_cookie',$log->after??[]);
    }
}
