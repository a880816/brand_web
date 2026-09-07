<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Material;
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaterialController extends Controller
{
    public function index(BrandContext $context){$this->authorize('viewAny',Material::class);$materials=Material::where('brand_id',$context->id())->orderBy('id')->paginate(20);return view('admin.shop.materials.index',compact('materials'));}
    public function create(){$this->authorize('create',Material::class);return view('admin.shop.materials.form',['material'=>new Material]);}
    public function store(Request $request,BrandContext $context,AuditService $audit){$this->authorize('create',Material::class);$material=Material::create($this->validated($request,$context)+['brand_id'=>$context->id()]);$audit->record('materials.created',$material,[],$material->toArray());return redirect()->route('admin.materials.edit',$material)->with('status','資材已建立。');}
    public function edit(Material $material,BrandContext $context){$material=$this->tenant($material,$context);return view('admin.shop.materials.form',compact('material'));}
    public function update(Request $request,Material $material,BrandContext $context,AuditService $audit){$material=$this->tenant($material,$context);$this->authorize('update',$material);$data=$this->validated($request,$context,$material);abort_if($material->reserved_quantity>(int)$data['stock_on_hand'],422,'庫存不可低於保留數量。');$before=$material->toArray();$material->update($data);$audit->record('materials.updated',$material,$before,$material->fresh()->toArray());return back()->with('status','資材已更新。');}
    private function tenant(Material $material,BrandContext $context):Material{abort_unless($material->brand_id===$context->id(),404);$this->authorize('view',$material);return $material;}
    private function validated(Request $request,BrandContext $context,?Material $material=null):array{$data=$request->validate(['product_code'=>['required','max:100','regex:/^[\p{Han}A-Za-z0-9-]+$/u',Rule::unique('materials')->where(fn($q)=>$q->where('brand_id',$context->id()))->ignore($material?->id)],'name'=>'required|string|max:160','slug'=>['required','alpha_dash','max:160',Rule::unique('materials')->where(fn($q)=>$q->where('brand_id',$context->id()))->ignore($material?->id)],'description'=>'nullable|string|max:50000','price'=>'required|numeric|min:0|max:99999999','stock_on_hand'=>'required|integer|min:0|max:100000','low_stock_threshold'=>'required|integer|min:0|max:100000','status'=>'required|in:draft,published,inactive','seo_title'=>'nullable|string|max:255','seo_description'=>'nullable|string|max:1000','spec_keys'=>'nullable|array|max:30','spec_keys.*'=>'nullable|string|max:100','spec_values'=>'nullable|array|max:30','spec_values.*'=>'nullable|string|max:500']);$data['specifications']=collect($data['spec_keys']??[])->map(fn($key,$i)=>['name'=>$key,'value'=>$data['spec_values'][$i]??''])->filter(fn($row)=>filled($row['name']))->values()->all();unset($data['spec_keys'],$data['spec_values']);$data['published_at']=$data['status']==='published'?($material?->published_at??now()):null;return $data;}
}
