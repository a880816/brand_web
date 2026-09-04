<?php

namespace App\Livewire\Admin;

use App\Models\{Page, PageSection};
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Component;

class PageSectionEditor extends Component
{
    use AuthorizesRequests;

    public int $pageId;
    public ?int $editingId = null;
    public string $type = 'text';
    public string $variant = 'default';
    public string $heading = '';
    public string $body = '';
    public string $buttonLabel = '';
    public string $buttonUrl = '';
    public int $limit = 3;
    public bool $autoplay = false;
    public string $status = 'active';

    public function mount(int $pageId): void
    {
        $this->pageId = $pageId;
        $this->authorize('update', $this->page());
    }

    private function page(): Page
    {
        return Page::where('brand_id', app(BrandContext::class)->id())->findOrFail($this->pageId);
    }

    private function section(int $id): PageSection
    {
        return $this->page()->sections()->whereKey($id)->firstOrFail();
    }

    public function edit(int $id): void
    {
        $section = $this->section($id);
        $this->authorize('update', $section);
        $this->editingId = $section->id;
        $this->type = $section->type;
        $this->variant = $section->variant;
        $this->heading = $section->heading ?? '';
        $this->body = $section->body ?? '';
        $this->buttonLabel = data_get($section->settings, 'button_label', '');
        $this->buttonUrl = data_get($section->settings, 'button_url', '');
        $this->limit = (int) data_get($section->settings, 'limit', 3);
        $this->autoplay = (bool) data_get($section->settings, 'autoplay', false);
        $this->status = $section->status;
    }

    public function save(AuditService $audit): void
    {
        $page = $this->page();
        $this->authorize('update', $page);
        $data = $this->validate([
            'type' => ['required', Rule::in(PageSection::TYPES)],
            'variant' => ['required', Rule::in(PageSection::VARIANTS)],
            'heading' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:20000',
            'buttonLabel' => 'nullable|string|max:80',
            'buttonUrl' => ['nullable', 'string', 'max:2048', 'regex:/^(https?:\/\/|\/)[^\s]*$/i'],
            'limit' => 'required|integer|min:1|max:12',
            'autoplay' => 'boolean',
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ]);
        $payload = [
            'brand_id' => $page->brand_id,
            'type' => $data['type'],
            'variant' => $data['variant'],
            'heading' => $data['heading'] ?: null,
            'body' => $data['body'] ?: null,
            'status' => $data['status'],
            'settings' => array_filter([
                'button_label' => $data['buttonLabel'] ?: null,
                'button_url' => $data['buttonUrl'] ?: null,
                'limit' => $data['limit'],
                'autoplay' => $data['autoplay'],
            ], fn ($value) => $value !== null),
        ];
        if ($this->editingId) {
            $section = $this->section($this->editingId);
            $this->authorize('update', $section);
            $before = $section->toArray();
            $section->update($payload);
            $audit->record('page_sections.updated', $section, $before, $section->fresh()->toArray());
        } else {
            $section = $page->sections()->create($payload + ['sort_order' => (int) $page->sections()->max('sort_order') + 1]);
            $audit->record('page_sections.created', $section, [], $section->toArray());
        }
        $this->cancel();
        session()->flash('status', '頁面區塊已儲存。');
    }

    public function move(int $id, string $direction, AuditService $audit): void
    {
        $page = $this->page();
        $this->authorize('update', $page);
        $ids = $page->sections()->pluck('id')->all();
        $index = array_search($id, $ids, true);
        $target = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index === false || ! isset($ids[$target])) return;
        [$ids[$index], $ids[$target]] = [$ids[$target], $ids[$index]];
        foreach ($ids as $order => $sectionId) $page->sections()->whereKey($sectionId)->update(['sort_order' => $order]);
        $audit->record('page_sections.reordered', $page, [], ['ids' => $ids]);
    }

    public function delete(int $id, AuditService $audit): void
    {
        $section = $this->section($id);
        $this->authorize('delete', $section);
        $before = $section->toArray();
        $section->delete();
        $audit->record('page_sections.deleted', $section, $before);
        if ($this->editingId === $id) $this->cancel();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'heading', 'body', 'buttonLabel', 'buttonUrl', 'autoplay');
        $this->type = 'text';
        $this->variant = 'default';
        $this->limit = 3;
        $this->status = 'active';
        $this->resetValidation();
    }

    public function render()
    {
        $page = $this->page();
        $this->authorize('update', $page);
        return view('livewire.admin.page-section-editor', ['sections' => $page->sections()->with('media')->get()]);
    }
}
