<?php

namespace Tests\Feature;

use App\Models\{Brand, Material, PlantSpecimen, PlantVariety, SaleOrder, User};
use App\Services\SaleOrderService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SaleOrderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp():void{parent::setUp();$this->withoutMiddleware([ValidateCsrfToken::class,\Illuminate\Routing\Middleware\ThrottleRequests::class]);Carbon::setTestNow('2026-09-04 10:00:00');}
    protected function tearDown():void{Carbon::setTestNow();parent::tearDown();}

    private function fixture():array
    {
        $brand=Brand::factory()->create(['slug'=>'verdant','domain'=>'brand-a.localhost','bank_name'=>'測試銀行','bank_account_number'=>'123456']);
        $admin=User::factory()->brandAdmin()->create();$admin->brands()->attach($brand);
        $variety=PlantVariety::create(['brand_id'=>$brand->id,'name'=>'鹿角蕨','scientific_name'=>'Platycerium test','variety_code'=>'PT-1','slug'=>'plant','status'=>'published','published_at'=>now()]);
        $exact=PlantSpecimen::forceCreate(['brand_id'=>$brand->id,'plant_variety_id'=>$variety->id,'sequence'=>1,'custom_name'=>'A株','full_tag_name'=>'Platycerium test PT-1 A株','price'=>1800,'stock_on_hand'=>1,'status'=>'published','published_at'=>now()]);
        $bulk=PlantSpecimen::forceCreate(['brand_id'=>$brand->id,'plant_variety_id'=>$variety->id,'sequence'=>2,'full_tag_name'=>'Platycerium test PT-1 2','price'=>1200,'stock_on_hand'=>3,'status'=>'published','published_at'=>now()]);
        $material=Material::create(['brand_id'=>$brand->id,'product_code'=>'MAT-1','name'=>'水苔','slug'=>'moss','price'=>300,'stock_on_hand'=>5,'low_stock_threshold'=>1,'status'=>'published','published_at'=>now()]);
        return [$brand,$admin,$exact,$bulk,$material];
    }

    private function specimenUrl(PlantSpecimen $item):string{return "http://brand-a.localhost:8085/shop/plants/plant/specimens/{$item->id}";}
    private function recipientData(array $overrides=[]):array{return array_merge(['customer_name'=>'王小明','phone'=>'0912345678','social_platform'=>'instagram','social_account'=>'@guest','shipping_method'=>'seven_eleven','store_no'=>'123456','store_name'=>'測試門市'],$overrides);}

    public function test_admin_pastes_urls_and_order_reserves_available_stock():void
    {
        [$brand,$admin,$exact,,$material]=$this->fixture();
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->post('/admin/orders',['lines'=>[['url'=>$this->specimenUrl($exact),'quantity'=>1],['url'=>'http://brand-a.localhost:8085/shop/materials/moss','quantity'=>2]]])->assertRedirect()->assertSessionHas('recipient_url');
        $order=SaleOrder::first();$this->assertSame('awaiting_recipient',$order->status);$this->assertSame('2400.00',$order->subtotal);$this->assertSame(1,$exact->fresh()->reserved_quantity);$this->assertSame(2,$material->fresh()->reserved_quantity);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->get('/admin/orders/'.$order->id.'/edit')->assertOk()->assertSee('水苔');
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->post('/admin/orders/'.$order->id.'/paid')->assertStatus(422);
        try{app(SaleOrderService::class)->create($brand,$admin,[['url'=>$this->specimenUrl($exact),'quantity'=>1]]);$this->fail('Expected duplicate reservation to fail');}catch(ValidationException $exception){$this->assertArrayHasKey('lines.0.quantity',$exception->errors());}
    }

    public function test_recipient_link_expires_and_shipping_updates_total():void
    {
        [$brand,$admin,$exact]=$this->fixture();[$order,$token]=app(SaleOrderService::class)->create($brand,$admin,[['url'=>$this->specimenUrl($exact),'quantity'=>1]]);
        $url="http://brand-a.localhost/order-recipient/{$order->reference}/{$token}";
        $this->get($url)->assertOk()->assertSee('7-11 店到店');
        $this->put($url,$this->recipientData())->assertOk()->assertSee('NT$ 1,880');
        $this->assertDatabaseHas('sale_orders',['id'=>$order->id,'status'=>'awaiting_payment','shipping_fee'=>80,'total'=>1880]);
        $this->get("http://brand-a.localhost/order-recipient/{$order->reference}/wrong")->assertStatus(410);
        $order->update(['recipient_link_expires_at'=>now()->subMinute()]);$this->get($url)->assertStatus(410);
    }

    public function test_payment_creates_individual_no_pick_sold_units_and_void_restores_stock():void
    {
        [$brand,$admin,,$bulk,$material]=$this->fixture();$service=app(SaleOrderService::class);[$order]=$service->create($brand,$admin,[['url'=>$this->specimenUrl($bulk),'quantity'=>2],['url'=>'https://brand-a.localhost/shop/materials/moss','quantity'=>1]]);
        $service->saveRecipient($order,$this->recipientData(['shipping_method'=>'self_pickup']),false);$service->markPaid($order);
        $this->assertSame(1,$bulk->fresh()->stock_on_hand);$this->assertSame(4,$material->fresh()->stock_on_hand);$this->assertDatabaseHas('plant_sold_units',['sold_code'=>'2-1']);$this->assertDatabaseHas('plant_sold_units',['sold_code'=>'2-2']);
        $service->void($order->fresh());$this->assertSame(3,$bulk->fresh()->stock_on_hand);$this->assertSame(5,$material->fresh()->stock_on_hand);$this->assertDatabaseCount('plant_sold_units',0);
    }

    public function test_cross_brand_product_url_is_rejected():void
    {
        [$brand,$admin]=$this->fixture();$other=Brand::factory()->create(['slug'=>'other','domain'=>'other.localhost']);$variety=PlantVariety::create(['brand_id'=>$other->id,'name'=>'外部','scientific_name'=>'Other','variety_code'=>'O-1','slug'=>'other','status'=>'published']);$foreign=PlantSpecimen::forceCreate(['brand_id'=>$other->id,'plant_variety_id'=>$variety->id,'sequence'=>1,'full_tag_name'=>'Other O-1 1','price'=>1,'stock_on_hand'=>1,'status'=>'published']);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost'])->post('/admin/orders',['lines'=>[['url'=>$this->specimenUrl($foreign),'quantity'=>1]]])->assertSessionHasErrors('lines.0.url');
        $this->assertDatabaseCount('sale_orders',0);
    }

    public function test_spoofed_host_and_public_form_honeypot_are_rejected():void
    {
        [$brand,$admin,$exact]=$this->fixture();
        try{app(SaleOrderService::class)->create($brand,$admin,[['url'=>"https://evil.example/shop/plants/plant/specimens/{$exact->id}",'quantity'=>1]]);$this->fail('Spoofed host accepted');}catch(ValidationException $exception){$this->assertArrayHasKey('lines.0.url',$exception->errors());}
        [$order,$token]=app(SaleOrderService::class)->create($brand,$admin,[['url'=>$this->specimenUrl($exact),'quantity'=>1]]);
        $this->put("http://brand-a.localhost/order-recipient/{$order->reference}/{$token}",$this->recipientData(['company_website'=>'spam.example']))->assertSessionHasErrors('company_website');
    }
}
