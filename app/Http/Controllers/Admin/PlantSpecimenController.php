<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{PlantSpecimen, PlantVariety};
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlantSpecimenController extends Controller
{
    public function create(PlantVariety $plantVariety, BrandContext $context)
    {
        $variety = $this->variety($plantVariety, $context); $this->authorize('create', PlantSpecimen::class);
        return view('admin.shop.specimens.form', ['variety'=>$variety,'specimen'=>new PlantSpecimen]);
    }

    public function store(Request $request, PlantVariety $plantVariety, BrandContext $context, AuditService $audit)
    {
        $variety = $this->variety($plantVariety, $context); $this->authorize('create', PlantSpecimen::class); $data=$this->validated($request);
        $specimen = DB::transaction(function () use ($variety,$context,$data) {
            $locked = PlantVariety::whereKey($variety->id)->lockForUpdate()->firstOrFail();
            $sequence = (int) PlantSpecimen::withTrashed()->where('plant_variety_id',$locked->id)->max('sequence') + 1;
            $fullTag = $this->fullTag($locked,$sequence,$data['custom_name']??null);
            if (PlantSpecimen::withTrashed()->where('full_tag_name',$fullTag)->exists()) throw ValidationException::withMessages(['custom_name'=>'完整花牌名稱已存在，請調整額外命名。']);
            $specimen = new PlantSpecimen($data);
            $specimen->brand_id = $context->id();
            $specimen->plant_variety_id = $locked->id;
            $specimen->sequence = $sequence;
            $specimen->full_tag_name = $fullTag;
            $specimen->save();
            return $specimen;
        });
        $audit->record('plant_specimens.created',$specimen,[],$specimen->toArray());
        return redirect()->route('admin.plant-specimens.edit',[$variety,$specimen])->with('status','實株已建立。');
    }

    public function edit(PlantVariety $plantVariety, PlantSpecimen $plantSpecimen, BrandContext $context)
    { $variety=$this->variety($plantVariety,$context);$specimen=$this->specimen($variety,$plantSpecimen,$context);return view('admin.shop.specimens.form',compact('variety','specimen')); }

    public function update(Request $request, PlantVariety $plantVariety, PlantSpecimen $plantSpecimen, BrandContext $context, AuditService $audit)
    {
        $variety=$this->variety($plantVariety,$context);$specimen=$this->specimen($variety,$plantSpecimen,$context);$this->authorize('update',$specimen);$data=$this->validated($request);
        abort_if($specimen->reserved_quantity > (int)$data['stock_on_hand'],422,'庫存不可低於保留數量。');
        $data['full_tag_name']=$this->fullTag($variety,$specimen->sequence,$data['custom_name']??null);
        if(PlantSpecimen::withTrashed()->where('full_tag_name',$data['full_tag_name'])->whereKeyNot($specimen->id)->exists()) throw ValidationException::withMessages(['custom_name'=>'完整花牌名稱已存在。']);
        $before=$specimen->toArray();$specimen->update($data);$audit->record('plant_specimens.updated',$specimen,$before,$specimen->fresh()->toArray());return back()->with('status','實株已更新。');
    }

    private function variety(PlantVariety $variety,BrandContext $context):PlantVariety{abort_unless($variety->brand_id===$context->id(),404);$this->authorize('view',$variety);return $variety;}
    private function specimen(PlantVariety $variety,PlantSpecimen $specimen,BrandContext $context):PlantSpecimen{abort_unless($specimen->brand_id===$context->id()&&$specimen->plant_variety_id===$variety->id,404);$this->authorize('view',$specimen);return $specimen;}
    private function fullTag(PlantVariety $variety,int $sequence,?string $custom):string{return trim($variety->scientific_name.' '.$variety->variety_code.' '.(filled($custom)?$custom:$sequence));}
    private function validated(Request $request):array
    {
        $data=$request->validate(['custom_name'=>['nullable','string','max:100','regex:/^(?![0-9]+$).+/u'],'description'=>'nullable|string|max:10000','price'=>'required|numeric|min:0|max:99999999','stock_on_hand'=>'required|integer|min:0|max:10000','status'=>'required|in:draft,published','spec_keys'=>'nullable|array|max:30','spec_keys.*'=>'nullable|string|max:100','spec_values'=>'nullable|array|max:30','spec_values.*'=>'nullable|string|max:500']);
        $data['specifications']=collect($data['spec_keys']??[])->map(fn($key,$i)=>['name'=>$key,'value'=>$data['spec_values'][$i]??''])->filter(fn($row)=>filled($row['name']))->values()->all();unset($data['spec_keys'],$data['spec_values']);$data['published_at']=$data['status']==='published'?now():null;return $data;
    }
}
