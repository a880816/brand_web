<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\AuditLog; use App\Support\BrandContext;
class DashboardController extends Controller { public function __invoke(BrandContext $context){if(auth()->user()->role==='member')return redirect()->route('admin.profile');$b=$context->brand();return view('admin.dashboard',['counts'=>['頁面'=>$b->pages()->count(),'服務'=>$b->services()->count(),'課程'=>$b->courses()->count(),'圖片'=>$b->media()->count()],'recent'=>AuditLog::where('brand_id',$b->id)->latest()->limit(8)->get(),'missing'=>['沒有啟用社群連結'=>$b->links()->count()===0,'尚未設定桌機 Hero'=>!$b->primaryMedia('hero_desktop')]]);}}
