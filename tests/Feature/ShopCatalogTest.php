<?php

namespace Tests\Feature;

use App\Models\{Brand, Material, PlantSpecimen, PlantVariety, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function context(): array
    {
        $brand=Brand::factory()->create(['slug'=>'verdant','domain'=>'brand-a.localhost','facebook_url'=>'https://facebook.com/brand']);
        $admin=User::factory()->brandAdmin()->create();$admin->brands()->attach($brand);
        return [$brand,$admin];
    }

    private function variety(Brand $brand,array $overrides=[]):PlantVariety
    {
        return PlantVariety::create(array_merge(['brand_id'=>$brand->id,'name'=>'皇冠鹿角蕨','scientific_name'=>'Platycerium ridleyi','variety_code'=>'皇冠-01','slug'=>'ridleyi','status'=>'published','published_at'=>now(),'sort_order'=>0],$overrides));
    }

    public function test_admin_creates_sequential_specimens_and_unique_full_tag_names(): void
    {
        [$brand,$admin]=$this->context();$variety=$this->variety($brand);
        $client=$this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost']);
        $client->post("/admin/plant-varieties/{$variety->id}/specimens",['custom_name'=>'A株','price'=>1800,'stock_on_hand'=>1,'status'=>'published'])->assertRedirect();
        $client->post("/admin/plant-varieties/{$variety->id}/specimens",['price'=>1600,'stock_on_hand'=>3,'status'=>'published'])->assertRedirect();
        $this->assertDatabaseHas('plant_specimens',['plant_variety_id'=>$variety->id,'sequence'=>1,'custom_name'=>'A株','full_tag_name'=>'Platycerium ridleyi 皇冠-01 A株']);
        $this->assertDatabaseHas('plant_specimens',['plant_variety_id'=>$variety->id,'sequence'=>2,'full_tag_name'=>'Platycerium ridleyi 皇冠-01 2','stock_on_hand'=>3]);
        $this->assertSame(4,$variety->fresh()->load('specimens')->availableStock());
    }

    public function test_specimen_custom_name_cannot_be_numeric_and_cross_brand_ids_are_rejected(): void
    {
        [$brand,$admin]=$this->context();$variety=$this->variety($brand);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])
            ->post("/admin/plant-varieties/{$variety->id}/specimens",['custom_name'=>'123','price'=>1000,'stock_on_hand'=>1,'status'=>'published'])->assertSessionHasErrors('custom_name');
        $other=Brand::factory()->create(['slug'=>'other','domain'=>'other.localhost']);$foreign=$this->variety($other,['variety_code'=>'OTHER','slug'=>'other']);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->get("/admin/plant-varieties/{$foreign->id}/edit")->assertNotFound();
    }

    public function test_shop_aggregates_plant_stock_and_displays_material_sold_out_state(): void
    {
        [$brand]=$this->context();$available=$this->variety($brand);
        $specimen=PlantSpecimen::forceCreate(['brand_id'=>$brand->id,'plant_variety_id'=>$available->id,'sequence'=>1,'full_tag_name'=>'Platycerium ridleyi 皇冠-01 1','price'=>1500,'stock_on_hand'=>2,'status'=>'published','published_at'=>now()]);
        $sold=$this->variety($brand,['name'=>'售完品種','scientific_name'=>'Platycerium sold','variety_code'=>'SOLD','slug'=>'sold','sort_order'=>0]);
        PlantSpecimen::forceCreate(['brand_id'=>$brand->id,'plant_variety_id'=>$sold->id,'sequence'=>1,'full_tag_name'=>'Platycerium sold SOLD 1','price'=>900,'stock_on_hand'=>0,'status'=>'published','published_at'=>now()]);
        Material::create(['brand_id'=>$brand->id,'product_code'=>'MAT-1','name'=>'水苔','slug'=>'moss','price'=>300,'stock_on_hand'=>0,'low_stock_threshold'=>2,'status'=>'published','published_at'=>now()]);

        $response=$this->get('http://brand-a.localhost/shop')->assertOk()->assertSee('在庫 2')->assertSee('NT$ 1,500 起')->assertSee('售完');
        $this->assertLessThan(strpos($response->getContent(),'售完品種'),strpos($response->getContent(),'皇冠鹿角蕨'));
        $this->get('http://brand-a.localhost/shop/plants/ridleyi')->assertOk()->assertSee('實株 1');
        $this->get('http://brand-a.localhost/shop/plants/ridleyi/specimens/'.$specimen->id)->assertOk()->assertSee('複製商品連結');
        $this->get('http://brand-a.localhost/shop/materials/moss')->assertOk()->assertSee('售完');
    }
}
