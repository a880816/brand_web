<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;

class BrandSettingsController extends Controller
{
    public function edit(BrandContext $context){$this->authorize('update',$context->brand());return view('admin.brand-settings');}

    public function update(Request $request,BrandContext $context,AuditService $audit)
    {
        $brand=$context->brand();$this->authorize('update',$brand);
        $rules=[
            'name'=>'required|string|max:160','primary_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','secondary_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','accent_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','background_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/','text_color'=>'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'seo_title'=>'nullable|string|max:255','seo_description'=>'nullable|string|max:1000','facebook_url'=>'nullable|url:http,https|max:2000','instagram_url'=>'nullable|url:http,https|max:2000',
            'home_menu_label'=>'required|string|max:30','courses_menu_label'=>'required|string|max:30','shop_menu_label'=>'required|string|max:30',
            'bank_name'=>'nullable|string|max:100','bank_code'=>'nullable|string|max:20','bank_branch'=>'nullable|string|max:100','bank_account_name'=>'nullable|string|max:100','bank_account_number'=>'nullable|string|max:100','remittance_notice'=>'nullable|string|max:2000',
        ];
        if($request->user()->isSuperAdmin())$rules+=['slug'=>'required|alpha_dash|max:100|unique:brands,slug,'.$brand->id,'domain'=>['nullable','max:255','regex:/^(?=.{1,253}$)(?!-)[A-Za-z0-9-]+(?:\.[A-Za-z0-9-]+)*(?<!-)$/'],'status'=>'required|in:active,inactive'];
        $data=$request->validate($rules);$before=$brand->toArray();$brand->update($data);$audit->record('brands.settings_updated',$brand,$before,$brand->fresh()->toArray());return back()->with('status','品牌設定已更新。');
    }
}
