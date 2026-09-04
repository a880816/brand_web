<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Course, Page, Service};
use App\Support\BrandContext;

class PreviewController extends Controller
{
    public function __invoke(string $resource, int $id, BrandContext $context)
    {
        $class = ['pages'=>Page::class,'services'=>Service::class,'courses'=>Course::class][$resource] ?? abort(404);
        $query = $class::where('brand_id', $context->id());
        if ($class === Page::class) $query->with(['sections.media']);
        $item = $query->findOrFail($id);
        $this->authorize('view', $item);
        view()->share('preview', true);

        if ($item instanceof Page) {
            if ($item->type === 'about') return view('site.about', ['page'=>$item]);
            return view('site.home', ['page'=>$item,'services'=>$context->brand()->services()->published()->limit(3)->get(),'courses'=>$context->brand()->courses()->published()->limit(3)->get(),'links'=>$context->brand()->links()->get()]);
        }
        return view('site.detail', ['kind'=>$item instanceof Course?'課程':'服務','item'=>$item,'back'=>$item instanceof Course?'courses.index':'services.index','links'=>$context->brand()->links()->get()]);
    }
}
