<?php

namespace App\Http\Controllers;

use App\Models\{Material, PlantSoldUnit, PlantSpecimen, PlantVariety};
use App\Support\BrandContext;
use Illuminate\Support\Facades\View;

class SiteController extends Controller
{
    public function __construct(private BrandContext $context) {}

    public function home()
    {
        $homepage = $this->context->brand()->homepageContent()->first();
        return $this->renderHome($homepage?->published_data ?? [], false);
    }

    public function renderHome(array $data, bool $preview = false)
    {
        $brand = $this->context->brand();
        $homepage = $brand->homepageContent()->first();
        $media = $homepage?->media()->get()->keyBy('id') ?? collect();
        $courseIds = array_values(array_map('intval', $data['featured_course_ids'] ?? []));
        $courseModels = $brand->courses()->published()->whereIn('id', $courseIds)->with(['plans', 'sessions.registrations'])->get()->keyBy('id');
        $courses = collect($courseIds)->map(fn ($id) => $courseModels->get($id))->filter();
        $gallery = collect($data['gallery_items'] ?? [])->map(function ($item) use ($media) {
            $item['media'] = $media->get((int) ($item['media_id'] ?? 0));
            return $item;
        })->filter(fn ($item) => $item['media']);
        $view = 'site.brands.'.$brand->slug.'.home';
        if (! View::exists($view)) $view = 'site.home';
        return view($view, compact('brand', 'homepage', 'data', 'media', 'courses', 'gallery', 'preview'));
    }

    public function courses()
    {
        $cutoff = now()->subMonths(config('courses.archive_after_months'));
        $courses = $this->context->brand()->courses()->published()
            ->where(fn ($query) => $query->whereDoesntHave('sessions')->orWhereHas('sessions', fn ($sessions) => $sessions->where('ends_at', '>=', $cutoff)))
            ->with(['sessions' => fn ($query) => $query->where('ends_at', '>=', now())->where('status', '!=', 'cancelled')->with('registrations'), 'plans'])
            ->orderBy('sort_order')->get()
            ->sortBy(fn ($course) => $course->sessions->contains(fn ($session) => $session->availabilityStatus() === 'open') ? 0 : 1)
            ->values();

        return view('site.courses.index', compact('courses'));
    }

    public function course(string $slug)
    {
        $course = $this->context->brand()->courses()->published()->where('slug', $slug)
            ->with(['plans', 'sessions' => fn ($query) => $query->where('ends_at', '>=', now())->where('status', '!=', 'cancelled')->with(['plans', 'registrations'])])
            ->firstOrFail();

        return view('site.courses.show', compact('course'));
    }

    public function shop()
    {
        $varieties = PlantVariety::where('brand_id',$this->context->id())->published()->whereHas('specimens',fn($q)=>$q->where('status','published'))->with(['specimens'=>fn($q)=>$q->where('status','published')])->orderBy('sort_order')->get()->sortByDesc(fn($v)=>$v->availableStock()>0)->values();
        $materials = Material::where('brand_id',$this->context->id())->published()->orderByRaw('(stock_on_hand - reserved_quantity) > 0 DESC')->orderBy('id')->get();
        $soldUnits = PlantSoldUnit::where('brand_id',$this->context->id())->with(['variety','specimen.media'])->latest('sold_at')->limit(4)->get();
        return view('site.shop.index',compact('varieties','materials','soldUnits'));
    }

    public function plant(string $slug)
    {
        $variety=PlantVariety::where('brand_id',$this->context->id())->published()->where('slug',$slug)->with(['specimens'=>fn($q)=>$q->where('status','published')->with('media')])->firstOrFail();
        return view('site.shop.plant',compact('variety'));
    }

    public function specimen(string $slug,PlantSpecimen $specimen)
    {
        $variety=PlantVariety::where('brand_id',$this->context->id())->published()->where('slug',$slug)->firstOrFail();
        abort_unless($specimen->brand_id===$this->context->id()&&$specimen->plant_variety_id===$variety->id&&$specimen->status==='published'&&$specimen->availableQuantity()>0,404);
        $specimen->load('media');return view('site.shop.specimen',compact('variety','specimen'));
    }

    public function material(string $slug)
    {
        $material=Material::where('brand_id',$this->context->id())->published()->where('slug',$slug)->firstOrFail();return view('site.shop.material',compact('material'));
    }
    public function about() { return view('site.about', ['page' => $this->context->brand()->pages()->with(['sections.media'])->published()->where('type', 'about')->firstOrFail()]); }
    public function services() { return view('site.index', ['kind' => '服務', 'items' => $this->context->brand()->services()->published()->orderBy('sort_order')->get(), 'route' => 'services.show']); }
    public function service(string $slug) { return view('site.detail', ['kind' => '服務', 'item' => $this->context->brand()->services()->published()->where('slug', $slug)->firstOrFail(), 'back' => 'services.index']); }
}
