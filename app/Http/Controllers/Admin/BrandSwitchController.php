<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller; use App\Models\Brand; use Illuminate\Http\Request;
class BrandSwitchController extends Controller { public function __invoke(Request $r,Brand $brand){abort_unless($r->user()->canManageBrand($brand),403);$r->session()->put('admin_brand_id',$brand->id);return back()->with('status','已切換至 '.$brand->name.'。');}}
