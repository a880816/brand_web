<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseController extends Controller
{
    public function index(BrandContext $context)
    {
        $this->authorize('viewAny', Course::class);
        $courses = Course::where('brand_id', $context->id())
            ->withCount(['sessions', 'registrations'])
            ->orderBy('sort_order')->orderBy('id')->paginate(20);

        return view('admin.courses.index', compact('courses'));
    }

    public function create()
    {
        $this->authorize('create', Course::class);
        return view('admin.courses.form', ['course' => new Course]);
    }

    public function store(Request $request, BrandContext $context, AuditService $audit)
    {
        $this->authorize('create', Course::class);
        [$data, $plans] = $this->validated($request, $context);
        $course = DB::transaction(function () use ($data, $plans, $context) {
            $course = Course::create($data + ['brand_id' => $context->id()]);
            $this->syncPlans($course, $plans, $context->id());
            return $course;
        });
        $audit->record('courses.created', $course, [], $course->load('plans')->toArray());

        return redirect()->route('admin.courses.edit', $course)->with('status', '課程已建立。');
    }

    public function edit(Course $course, BrandContext $context)
    {
        $course = $this->tenantCourse($course, $context);
        return view('admin.courses.form', compact('course'));
    }

    public function update(Request $request, Course $course, BrandContext $context, AuditService $audit)
    {
        $course = $this->tenantCourse($course, $context);
        $this->authorize('update', $course);
        [$data, $plans] = $this->validated($request, $context, $course);
        $before = $course->load('plans')->toArray();
        DB::transaction(function () use ($course, $data, $plans, $context) {
            $course->update($data);
            $this->syncPlans($course, $plans, $context->id());
        });
        $audit->record('courses.updated', $course, $before, $course->fresh()->load('plans')->toArray());

        return back()->with('status', '課程已更新。');
    }

    public function publish(Course $course, BrandContext $context, AuditService $audit)
    {
        $course = $this->tenantCourse($course, $context);
        $this->authorize('update', $course);
        abort_if($course->plans()->where('is_enabled', true)->doesntExist(), 422, '至少需要一個啟用方案。');
        $before = $course->toArray();
        $course->update(['status' => 'published', 'published_at' => $course->published_at ?? now()]);
        $audit->record('courses.published', $course, $before, $course->fresh()->toArray());
        return back()->with('status', '課程已發布。');
    }

    public function unpublish(Course $course, BrandContext $context, AuditService $audit)
    {
        $course = $this->tenantCourse($course, $context);
        $this->authorize('update', $course);
        $before = $course->toArray();
        $course->update(['status' => 'draft']);
        $audit->record('courses.unpublished', $course, $before, $course->fresh()->toArray());
        return back()->with('status', '課程已下架。');
    }

    public function destroy(Course $course, BrandContext $context, AuditService $audit)
    {
        $course = $this->tenantCourse($course, $context);
        $this->authorize('delete', $course);
        abort_if($course->registrations()->exists(), 422, '已有報名紀錄的課程不可刪除，請改為下架。');
        $audit->record('courses.deleted', $course, $course->toArray());
        $course->delete();
        return redirect()->route('admin.courses.index')->with('status', '課程已刪除。');
    }

    private function tenantCourse(Course $course, BrandContext $context): Course
    {
        abort_unless($course->brand_id === $context->id(), 404);
        $this->authorize('view', $course);
        return $course;
    }

    private function validated(Request $request, BrandContext $context, ?Course $course = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:160',
            'slug' => ['required', 'alpha_dash', 'max:160', Rule::unique('courses')->where(fn ($q) => $q->where('brand_id', $context->id()))->ignore($course?->id)],
            'summary' => 'nullable|string|max:1000',
            'description' => 'nullable|string|max:50000',
            'suitable_for' => 'nullable|string|max:10000',
            'precautions' => 'nullable|string|max:10000',
            'notion_url' => ['nullable', 'url:http,https', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                if (! filled($value)) return;
                $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                if (! in_array($host, ['notion.so', 'www.notion.so'], true) && ! str_ends_with($host, '.notion.site')) {
                    $fail('行前通知網址必須使用 Notion 網域。');
                }
            }],
            'duration_minutes' => 'nullable|integer|min:1|max:10080',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'seo_title' => 'nullable|string|max:255',
            'seo_description' => 'nullable|string|max:1000',
            'plans' => 'required|array|min:1|max:12',
            'plans.*.name' => 'required|string|max:100',
            'plans.*.participants' => 'required|integer|min:1|max:100',
            'plans.*.price' => 'required|numeric|min:0|max:99999999',
            'plans.*.is_enabled' => 'nullable|boolean',
        ]);
        $plans = $data['plans'];
        unset($data['plans']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['status'] = $course?->status ?? 'draft';

        return [$data, $plans];
    }

    private function syncPlans(Course $course, array $plans, int $brandId): void
    {
        $course->plans()->delete();
        foreach (array_values($plans) as $index => $plan) {
            $course->plans()->create([
                'brand_id' => $brandId,
                'name' => $plan['name'],
                'participants' => $plan['participants'],
                'price' => $plan['price'],
                'sort_order' => $index,
                'is_enabled' => (bool) ($plan['is_enabled'] ?? false),
            ]);
        }
    }
}
