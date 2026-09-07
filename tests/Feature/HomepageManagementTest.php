<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, HomepageContent, Material, PlantSpecimen, PlantVariety, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function context(): array
    {
        $brand = Brand::factory()->create(['slug' => 'verdant', 'domain' => 'brand-a.localhost']);
        $admin = User::factory()->brandAdmin()->create();
        $admin->brands()->attach($brand);
        return [$brand, $admin];
    }

    public function test_draft_preview_and_publish_are_separate(): void
    {
        [$brand, $admin] = $this->context();
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/homepage', ['hero_title' => '尚未發布標題'])
            ->assertRedirect();

        $this->get('http://brand-a.localhost/')->assertDontSee('尚未發布標題');
        $this->actingAs($admin)->get('http://brand-a.localhost/admin/homepage/preview')
            ->assertOk()->assertSee('草稿預覽')->assertSee('尚未發布標題');
        $this->actingAs($admin)->post('http://brand-a.localhost/admin/homepage/publish')->assertRedirect();
        $this->get('http://brand-a.localhost/')->assertOk()->assertSee('尚未發布標題');
        $this->assertNotNull(HomepageContent::first()->published_at);
    }

    public function test_featured_courses_cannot_cross_brand_or_exceed_six(): void
    {
        [$brand, $admin] = $this->context();
        $foreignBrand = Brand::factory()->create(['slug' => 'terracotta', 'domain' => 'brand-b.localhost']);
        $foreignCourse = Course::factory()->create(['brand_id' => $foreignBrand->id]);

        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/homepage', ['featured_course_ids' => [$foreignCourse->id]])
            ->assertSessionHasErrors('featured_course_ids.0');

        $courses = Course::factory()->count(7)->create(['brand_id' => $brand->id]);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/homepage', ['featured_course_ids' => $courses->pluck('id')->all()])
            ->assertSessionHasErrors('featured_course_ids');
    }

    public function test_each_known_brand_uses_an_independent_home_template(): void
    {
        $a = Brand::factory()->create(['slug' => 'verdant', 'domain' => 'brand-a.localhost']);
        $b = Brand::factory()->create(['slug' => 'terracotta', 'domain' => 'brand-b.localhost']);
        $a->homepageContent()->create(['published_data' => ['hero_title' => 'Brand A'], 'draft_data' => []]);
        $b->homepageContent()->create(['published_data' => ['hero_title' => 'Brand B'], 'draft_data' => []]);

        $this->get('http://brand-a.localhost/')->assertSee('brand-home-verdant')->assertSee('>Brand A<', false)->assertDontSee('>Brand B<', false);
        $this->get('http://brand-b.localhost/')->assertSee('brand-home-terracotta')->assertSee('>Brand B<', false)->assertDontSee('>Brand A<', false);
    }

    public function test_featured_products_are_tenant_scoped_and_sold_out_plants_are_hidden(): void
    {
        [$brand,$admin]=$this->context();
        $plant=PlantVariety::create(['brand_id'=>$brand->id,'name'=>'精選植株','scientific_name'=>'Plant A','variety_code'=>'A-1','slug'=>'plant-a','status'=>'published','published_at'=>now()]);
        PlantSpecimen::forceCreate(['brand_id'=>$brand->id,'plant_variety_id'=>$plant->id,'sequence'=>1,'full_tag_name'=>'Plant A A-1 1','price'=>1000,'stock_on_hand'=>1,'status'=>'published']);
        $sold=PlantVariety::create(['brand_id'=>$brand->id,'name'=>'零庫存植株','scientific_name'=>'Plant B','variety_code'=>'B-1','slug'=>'plant-b','status'=>'published','published_at'=>now()]);
        PlantSpecimen::forceCreate(['brand_id'=>$brand->id,'plant_variety_id'=>$sold->id,'sequence'=>1,'full_tag_name'=>'Plant B B-1 1','price'=>1000,'stock_on_hand'=>0,'status'=>'published']);
        $material=Material::create(['brand_id'=>$brand->id,'product_code'=>'M-1','name'=>'精選資材','slug'=>'material','price'=>200,'stock_on_hand'=>3,'low_stock_threshold'=>1,'status'=>'published','published_at'=>now()]);
        $other=Brand::factory()->create(['slug'=>'other','domain'=>'other.localhost']);
        $foreign=Material::create(['brand_id'=>$other->id,'product_code'=>'X','name'=>'外部資材','slug'=>'external','price'=>1,'stock_on_hand'=>1,'low_stock_threshold'=>0,'status'=>'published']);
        $client=$this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost']);
        $client->put('/admin/homepage',['featured_product_keys'=>['material:'.$foreign->id]])->assertStatus(422);
        $client->put('/admin/homepage',['featured_product_keys'=>['plant:'.$plant->id,'plant:'.$sold->id,'material:'.$material->id]])->assertRedirect();
        $client->post('/admin/homepage/publish')->assertRedirect();
        $this->get('http://brand-a.localhost/')->assertOk()->assertSee('精選植株')->assertSee('精選資材')->assertDontSee('零庫存植株')->assertDontSee('外部資材');
    }
}
