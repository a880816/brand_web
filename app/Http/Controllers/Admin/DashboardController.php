<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{CourseRegistration, CourseSession, Material, PlantSpecimen, SaleOrder};
use App\Support\BrandContext;

class DashboardController extends Controller
{
    public function __invoke(BrandContext $context)
    {
        $brandId=$context->id();$monthStart=now()->startOfMonth();
        $paid=SaleOrder::where('brand_id',$brandId)->where('status','paid')->where('paid_at','>=',$monthStart);
        $stats=[
            '本月成交單'=>$paid->count(),
            '本月成交金額'=>'NT$ '.number_format((float)(clone $paid)->sum('total')),
            '待填收件資料'=>SaleOrder::where('brand_id',$brandId)->where('status','awaiting_recipient')->count(),
            '待確認匯款'=>SaleOrder::where('brand_id',$brandId)->where('status','awaiting_payment')->count(),
        ];
        $registrationCounts=[
            '等待匯款'=>CourseRegistration::where('brand_id',$brandId)->where('status','awaiting_payment')->where('payment_due_at','>=',now())->count(),
            '等待核對'=>CourseRegistration::where('brand_id',$brandId)->where('status','awaiting_review')->count(),
            '匯款逾期'=>CourseRegistration::where('brand_id',$brandId)->where('status','awaiting_payment')->where('payment_due_at','<',now())->count(),
        ];
        $sessions=CourseSession::where('brand_id',$brandId)->where('starts_at','>=',now())->where('status','!=','cancelled')->with(['course','registrations'])->orderBy('starts_at')->limit(5)->get();
        $materials=Material::where('brand_id',$brandId)->where('status','published')->get()->filter(fn($item)=>$item->availableQuantity()<=$item->low_stock_threshold);
        $specimens=PlantSpecimen::where('brand_id',$brandId)->where('status','published')->with('variety')->get()->filter(fn($item)=>$item->availableQuantity()===0);
        return view('admin.dashboard',compact('stats','registrationCounts','sessions','materials','specimens'));
    }
}
