<?php

namespace App\Http\Controllers;

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
        return view('site.index', ['kind' => '課程', 'items' => $this->context->brand()->courses()->published()->orderBy('sort_order')->get(), 'route' => 'courses.show']);
    }

    public function course(string $slug)
    {
        return view('site.detail', ['kind' => '課程', 'item' => $this->context->brand()->courses()->published()->where('slug', $slug)->firstOrFail(), 'back' => 'courses.index']);
    }

    public function shop() { return view('site.shop.index'); }
    public function about() { return view('site.about', ['page' => $this->context->brand()->pages()->with(['sections.media'])->published()->where('type', 'about')->firstOrFail()]); }
    public function services() { return view('site.index', ['kind' => '服務', 'items' => $this->context->brand()->services()->published()->orderBy('sort_order')->get(), 'route' => 'services.show']); }
    public function service(string $slug) { return view('site.detail', ['kind' => '服務', 'item' => $this->context->brand()->services()->published()->where('slug', $slug)->firstOrFail(), 'back' => 'services.index']); }
}
