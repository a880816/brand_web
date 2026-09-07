<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HomepageContent;
use App\Models\{Material, PlantVariety};
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class HomepageController extends Controller
{
    public function edit(BrandContext $context)
    {
        $content = $this->content($context);
        $this->authorize('update', $content);
        return view('admin.homepage.edit', [
            'homepage' => $content,
            'draft' => $content->draft_data,
            'courses' => $context->brand()->courses()->orderBy('sort_order')->get(),
            'varieties' => PlantVariety::where('brand_id',$context->id())->orderBy('sort_order')->get(),
            'materials' => Material::where('brand_id',$context->id())->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, BrandContext $context, AuditService $audit)
    {
        $content = $this->content($context);
        $this->authorize('update', $content);
        $data = $request->validate([
            'hero_title' => 'nullable|string|max:160',
            'hero_subtitle' => 'nullable|string|max:500',
            'hero_desktop_media_id' => 'nullable|integer',
            'hero_mobile_media_id' => 'nullable|integer',
            'hero_primary_label' => 'nullable|string|max:40',
            'hero_secondary_label' => 'nullable|string|max:40',
            'intro_title' => 'nullable|string|max:160',
            'intro_body' => 'nullable|string|max:3000',
            'intro_media_id' => 'nullable|integer',
            'intro_link_label' => 'nullable|string|max:40',
            'intro_link_url' => 'nullable|url:http,https|max:2048',
            'featured_course_ids' => 'nullable|array|max:6',
            'featured_course_ids.*' => ['integer', Rule::exists('courses', 'id')->where('brand_id', $context->id())],
            'featured_product_keys' => 'nullable|array|max:12',
            'featured_product_keys.*' => ['string','regex:/^(plant|material):[0-9]+$/'],
            'gallery' => 'nullable|array|max:20',
            'gallery.*.enabled' => 'nullable|boolean',
            'gallery.*.title' => 'nullable|string|max:160',
            'gallery.*.body' => 'nullable|string|max:500',
            'gallery.*.url' => 'nullable|url:http,https|max:2048',
        ]);

        $media = $content->media()->pluck('collection', 'id');
        foreach (['hero_desktop_media_id' => 'hero_desktop', 'hero_mobile_media_id' => 'hero_mobile', 'intro_media_id' => 'intro'] as $field => $collection) {
            if (filled($data[$field] ?? null) && $media->get((int) $data[$field]) !== $collection) abort(422, '圖片與首頁區塊不符。');
        }

        $gallery = collect($data['gallery'] ?? [])->map(function ($item, $id) use ($media) {
            abort_unless($media->get((int) $id) === 'gallery', 422, '輪播圖片不正確。');
            return ['media_id' => (int) $id, 'title' => $item['title'] ?? null, 'body' => $item['body'] ?? null, 'url' => $item['url'] ?? null, 'enabled' => (bool) ($item['enabled'] ?? false)];
        })->filter(fn ($item) => $item['enabled'])->values()->all();

        unset($data['gallery']);
        $data['gallery_items'] = $gallery;
        $data['featured_course_ids'] = array_values(array_unique(array_map('intval', $data['featured_course_ids'] ?? [])));
        $data['featured_product_keys'] = array_values(array_unique($data['featured_product_keys'] ?? []));
        foreach($data['featured_product_keys'] as $key){[$type,$id]=explode(':',$key,2);$model=$type==='plant'?PlantVariety::class:Material::class;abort_unless($model::where('brand_id',$context->id())->whereKey((int)$id)->exists(),422,'精選商品與品牌不符。');}
        $before = $content->draft_data;
        $content->update(['draft_data' => $data]);
        $audit->record('homepage.draft_saved', $content, $before, $data);
        return back()->with('status', '首頁草稿已儲存。');
    }

    public function publish(BrandContext $context, AuditService $audit)
    {
        $content = $this->content($context);
        $this->authorize('update', $content);
        $before = $content->published_data;
        $content->update(['published_data' => $content->draft_data, 'published_at' => now()]);
        $audit->record('homepage.published', $content, $before, $content->published_data);
        return back()->with('status', '首頁已發布。');
    }

    public function preview(BrandContext $context)
    {
        $content = $this->content($context);
        $this->authorize('view', $content);
        return app(\App\Http\Controllers\SiteController::class)->renderHome($content->draft_data, true);
    }

    private function content(BrandContext $context): HomepageContent
    {
        return $context->brand()->homepageContent()->firstOrCreate([], [
            'draft_data' => ['hero_primary_label' => '探索課程', 'hero_secondary_label' => '逛逛商店'],
            'published_data' => [],
        ]);
    }
}
