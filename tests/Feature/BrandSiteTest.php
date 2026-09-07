<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, HomepageContent, Material, Media, PlantSpecimen, PlantVariety};
use App\Services\MediaService;
use App\Support\BrandContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BrandSiteTest extends TestCase
{
    use RefreshDatabase;
    private function brand(string $slug='verdant',string $domain='brand-a.localhost'):Brand{return Brand::factory()->create(compact('slug','domain'));}
    private function home(Brand $brand,string $title):HomepageContent{return HomepageContent::create(['brand_id'=>$brand->id,'draft_data'=>[],'published_data'=>['hero_title'=>$title],'published_at'=>now()]);}

    public function test_each_hostname_renders_only_its_brand_homepage():void
    {
        $a=$this->brand();$b=$this->brand('terracotta','brand-b.localhost');$this->home($a,'A 品牌首頁');$this->home($b,'B 品牌首頁');
        $this->get('http://brand-a.localhost/')->assertOk()->assertSee('A 品牌首頁')->assertDontSee('B 品牌首頁');
        $this->get('http://brand-b.localhost/')->assertOk()->assertSee('B 品牌首頁')->assertDontSee('A 品牌首頁');
    }

    public function test_cross_brand_catalog_records_are_not_addressable():void
    {
        $a=$this->brand();$b=$this->brand('terracotta','brand-b.localhost');
        $variety=PlantVariety::create(['brand_id'=>$b->id,'name'=>'外品牌','scientific_name'=>'Other','variety_code'=>'O-1','slug'=>'other','status'=>'published','published_at'=>now()]);
        $material=Material::create(['brand_id'=>$b->id,'product_code'=>'M-1','name'=>'外品牌資材','slug'=>'other','price'=>1,'stock_on_hand'=>1,'reserved_quantity'=>0,'low_stock_threshold'=>0,'status'=>'published','published_at'=>now()]);
        $this->get('http://brand-a.localhost/shop/plants/'.$variety->slug)->assertNotFound();$this->get('http://brand-a.localhost/shop/materials/'.$material->slug)->assertNotFound();
    }

    public function test_only_currently_published_courses_and_materials_are_visible():void
    {
        $brand=$this->brand();Course::factory()->create(['brand_id'=>$brand->id,'name'=>'公開課程','slug'=>'visible','status'=>'published','published_at'=>now()]);Course::factory()->create(['brand_id'=>$brand->id,'name'=>'草稿課程','slug'=>'draft','status'=>'draft']);Course::factory()->create(['brand_id'=>$brand->id,'name'=>'未來課程','slug'=>'future','status'=>'published','published_at'=>now()->addDay()]);
        foreach([['公開資材','visible','published',now()],['草稿資材','draft','draft',null],['未來資材','future','published',now()->addDay()]] as [$name,$slug,$status,$at])Material::create(['brand_id'=>$brand->id,'product_code'=>strtoupper($slug),'name'=>$name,'slug'=>$slug,'price'=>1,'stock_on_hand'=>1,'reserved_quantity'=>0,'low_stock_threshold'=>0,'status'=>$status,'published_at'=>$at]);
        $this->get('http://brand-a.localhost/courses')->assertSee('公開課程')->assertDontSee('草稿課程')->assertDontSee('未來課程');
        $this->get('http://brand-a.localhost/shop')->assertSee('公開資材')->assertDontSee('草稿資材')->assertDontSee('未來資材');
    }

    public function test_current_content_models_support_ordered_primary_media():void
    {
        $brand=$this->brand();$home=$this->home($brand,'首頁');
        Media::factory()->create(['brand_id'=>$brand->id,'mediable_type'=>$home->getMorphClass(),'mediable_id'=>$home->id,'sort_order'=>2]);
        $primary=Media::factory()->create(['brand_id'=>$brand->id,'mediable_type'=>$home->getMorphClass(),'mediable_id'=>$home->id,'sort_order'=>1,'is_primary'=>true]);
        $this->assertSame($primary->id,$home->primaryMedia('gallery')->id);$this->assertSame([1,2],$home->mediaFor('gallery')->pluck('sort_order')->all());
    }

    public function test_media_service_rejects_cross_brand_owner():void
    {
        $a=$this->brand();$b=$this->brand('terracotta','brand-b.localhost');$home=$this->home($b,'B');app(BrandContext::class)->set($a);
        $this->expectException(ValidationException::class);app(MediaService::class)->store($home,UploadedFile::fake()->image('leaf.jpg'),'gallery');
    }

    public function test_legacy_tables_are_removed_and_current_tables_have_no_image_columns():void
    {
        foreach(['pages','services','page_sections','brand_links'] as $table)$this->assertFalse(Schema::hasTable($table));
        foreach(['courses','plant_varieties','plant_specimens','materials'] as $table)foreach(Schema::getColumnListing($table) as $column)$this->assertDoesNotMatchRegularExpression('/^(image|cover|logo|favicon|hero)(_path|_\d+)?$/',$column);
    }

    public function test_social_links_are_safe_and_unknown_route_is_404():void
    {
        $brand=$this->brand();$brand->update(['facebook_url'=>'https://facebook.com/brand','instagram_url'=>'https://instagram.com/brand']);$this->home($brand,'首頁');
        $this->get('http://brand-a.localhost/')->assertSee('Facebook')->assertSee('Instagram')->assertSee('noopener noreferrer',false);
        $this->get('http://brand-a.localhost/not-here')->assertNotFound();
    }
}
