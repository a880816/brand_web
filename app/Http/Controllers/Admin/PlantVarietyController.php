<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlantVariety;
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlantVarietyController extends Controller
{
    public function index(BrandContext $context)
    {
        $this->authorize('viewAny', PlantVariety::class);
        $varieties = PlantVariety::where('brand_id', $context->id())->with('specimens')->orderBy('sort_order')->paginate(20);
        return view('admin.shop.varieties.index', compact('varieties'));
    }

    public function create() { $this->authorize('create', PlantVariety::class); return view('admin.shop.varieties.form', ['variety' => new PlantVariety]); }

    public function store(Request $request, BrandContext $context, AuditService $audit)
    {
        $this->authorize('create', PlantVariety::class);
        $variety = PlantVariety::create($this->validated($request, $context) + ['brand_id' => $context->id()]);
        $audit->record('plant_varieties.created', $variety, [], $variety->toArray());
        return redirect()->route('admin.plant-varieties.edit', $variety)->with('status', '品種已建立，可繼續新增實株。');
    }

    public function edit(PlantVariety $plantVariety, BrandContext $context)
    {
        $variety = $this->tenant($plantVariety, $context);
        return view('admin.shop.varieties.form', compact('variety'));
    }

    public function update(Request $request, PlantVariety $plantVariety, BrandContext $context, AuditService $audit)
    {
        $variety = $this->tenant($plantVariety, $context); $this->authorize('update', $variety);
        $before = $variety->toArray(); $variety->update($this->validated($request, $context, $variety));
        $audit->record('plant_varieties.updated', $variety, $before, $variety->fresh()->toArray());
        return back()->with('status', '品種已更新。');
    }

    public function destroy(PlantVariety $plantVariety, BrandContext $context, AuditService $audit)
    {
        $variety = $this->tenant($plantVariety, $context); $this->authorize('delete', $variety);
        abort_if($variety->specimens()->exists(), 422, '請先處理此品種的實株資料。');
        $audit->record('plant_varieties.deleted', $variety, $variety->toArray()); $variety->delete();
        return redirect()->route('admin.plant-varieties.index')->with('status', '品種已刪除。');
    }

    private function tenant(PlantVariety $variety, BrandContext $context): PlantVariety { abort_unless($variety->brand_id === $context->id(), 404); $this->authorize('view', $variety); return $variety; }
    private function validated(Request $request, BrandContext $context, ?PlantVariety $variety = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:160', 'scientific_name' => 'required|string|max:200',
            'variety_code' => ['required', 'max:100', 'regex:/^[\p{Han}A-Za-z0-9-]+$/u', Rule::unique('plant_varieties')->where(fn($q) => $q->where('brand_id',$context->id()))->ignore($variety?->id)],
            'slug' => ['required','alpha_dash','max:160',Rule::unique('plant_varieties')->where(fn($q)=>$q->where('brand_id',$context->id()))->ignore($variety?->id)],
            'description' => 'nullable|string|max:50000', 'care_instructions' => 'nullable|string|max:20000',
            'status' => ['required',Rule::in(['draft','published'])], 'sort_order' => 'nullable|integer|min:0|max:9999',
            'seo_title' => 'nullable|string|max:255', 'seo_description' => 'nullable|string|max:1000',
        ]) + ['sort_order' => (int)$request->input('sort_order',0), 'published_at' => $request->input('status') === 'published' ? ($variety?->published_at ?? now()) : null];
    }
}
