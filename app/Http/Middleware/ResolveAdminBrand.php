<?php

namespace App\Http\Middleware;

use App\Models\Brand;
use App\Support\BrandContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveAdminBrand
{
    public function __construct(private BrandContext $context){}

    public function handle(Request $request,Closure $next):Response
    {
        $user=$request->user();
        abort_unless(in_array($user->role,['brand_admin','super_admin'],true),403);
        $candidate=(int)$request->session()->get('admin_brand_id',$this->context->id());
        $brand=Brand::query()->whereKey($candidate)->where('status','active')->first();
        if(!$brand||!$user->canManageBrand($brand))$brand=$user->isSuperAdmin()?Brand::where('status','active')->first():$user->brands()->where('status','active')->first();
        abort_unless($brand,403,'沒有可管理的品牌。');
        $this->context->set($brand);$request->session()->put('admin_brand_id',$brand->id);view()->share('brand',$brand);
        view()->share('adminBrands',$user->isSuperAdmin()?Brand::where('status','active')->orderBy('name')->get():$user->brands()->where('status','active')->orderBy('name')->get());
        return $next($request);
    }
}
