<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, CourseRegistration, CourseSession, Material, SaleOrder, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OperationsDashboardTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp():void{parent::setUp();$this->withoutMiddleware(ValidateCsrfToken::class);}

    public function test_brand_settings_and_dashboard_cover_daily_operations():void
    {
        $brand=Brand::factory()->create(['slug'=>'verdant','domain'=>'brand-a.localhost']);$admin=User::factory()->brandAdmin()->create();$admin->brands()->attach($brand);
        $client=$this->actingAs($admin)->withServerVariables(['HTTP_HOST'=>'brand-a.localhost']);
        $client->put('/admin/settings',['name'=>'新品牌名','slug'=>'hacked','domain'=>'evil.test','primary_color'=>'#112233','secondary_color'=>'#223344','accent_color'=>'#334455','background_color'=>'#fefefe','text_color'=>'#111111','home_menu_label'=>'首頁','courses_menu_label'=>'課程','shop_menu_label'=>'商店','facebook_url'=>'https://facebook.com/new','bank_name'=>'測試銀行','bank_code'=>'999','bank_branch'=>'台北','bank_account_name'=>'品牌戶名','bank_account_number'=>'123456','remittance_notice'=>'請私訊末五碼'])->assertRedirect()->assertSessionHasNoErrors();
        $brand=$brand->fresh();$this->assertSame('verdant',$brand->slug);$this->assertSame('brand-a.localhost',$brand->domain);$this->assertSame('測試銀行',$brand->bank_name);
        SaleOrder::forceCreate(['reference'=>(string)Str::uuid(),'brand_id'=>$brand->id,'created_by'=>$admin->id,'status'=>'paid','subtotal'=>3000,'shipping_fee'=>0,'total'=>3000,'free_shipping'=>false,'bank_snapshot'=>[],'paid_at'=>now()]);
        SaleOrder::forceCreate(['reference'=>(string)Str::uuid(),'brand_id'=>$brand->id,'created_by'=>$admin->id,'status'=>'awaiting_recipient','subtotal'=>1000,'shipping_fee'=>0,'total'=>1000,'free_shipping'=>false,'bank_snapshot'=>[]]);
        Material::create(['brand_id'=>$brand->id,'product_code'=>'LOW','name'=>'低庫存資材','slug'=>'low','price'=>100,'stock_on_hand'=>1,'low_stock_threshold'=>2,'status'=>'published']);
        $course=Course::factory()->create(['brand_id'=>$brand->id]);
        $plan=$course->plans()->create(['brand_id'=>$brand->id,'name'=>'單人','participants'=>1,'price'=>1000,'is_enabled'=>true]);
        $session=CourseSession::create(['brand_id'=>$brand->id,'course_id'=>$course->id,'starts_at'=>now()->addDay(),'ends_at'=>now()->addDay()->addHour(),'city'=>'台北','venue_name'=>'工作室','address'=>'地址','google_maps_url'=>'https://maps.google.com','capacity'=>5,'status'=>'open']);
        CourseRegistration::create(['reference'=>(string)Str::uuid(),'brand_id'=>$brand->id,'course_id'=>$course->id,'course_session_id'=>$session->id,'course_plan_id'=>$plan->id,'contact_name'=>'客人','phone'=>'0900','email'=>'a@example.com','social_platform'=>'line','social_account'=>'id','participants'=>1,'plan_name'=>'單人','amount'=>1000,'status'=>'awaiting_payment','payment_due_at'=>now()->subHour(),'bank_snapshot'=>[]]);
        $client->get('/admin')->assertOk()->assertSee('NT$ 3,000')->assertSee('待填收件資料')->assertSee('匯款逾期')->assertSee('低庫存資材')->assertSee('工作室')->assertDontSee('首頁草稿');
    }
}
