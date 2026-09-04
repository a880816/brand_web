<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Services\{AuditService, MediaService};
use App\Support\BrandContext;
use Illuminate\Http\Request;

class MediaLibraryController extends Controller
{
    private function item(int $id, BrandContext $context): Media
    {
        $media = Media::where('brand_id', $context->id())->findOrFail($id);
        $this->authorize('view', $media);
        return $media;
    }

    public function index(BrandContext $context)
    {
        $this->authorize('viewAny', Media::class);
        return view('admin.media.index', ['media'=>Media::where('brand_id',$context->id())->latest()->paginate(30)]);
    }

    public function update(Request $request, int $id, BrandContext $context, MediaService $service, AuditService $audit)
    {
        $media = $this->item($id, $context);
        $this->authorize('update', $media);
        $before = $media->toArray();
        $service->update($media, $request->validate(['alt_text'=>'nullable|string|max:255','title'=>'nullable|string|max:255']));
        $audit->record('media.updated', $media, $before, $media->fresh()->toArray());
        return back()->with('status', '圖片資訊已更新。');
    }

    public function destroy(int $id, BrandContext $context, MediaService $service, AuditService $audit)
    {
        $media = $this->item($id, $context);
        $this->authorize('delete', $media);
        $before = $media->toArray();
        $service->delete($media);
        $audit->record('media.deleted', null, $before, [], $context->id());
        return back()->with('status', '圖片已刪除。');
    }
}
