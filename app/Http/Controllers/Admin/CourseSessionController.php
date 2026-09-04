<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\{Course, CourseSession};
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CourseSessionController extends Controller
{
    public function create(Course $course, BrandContext $context)
    {
        $course = $this->course($course, $context);
        $this->authorize('create', CourseSession::class);
        return view('admin.courses.session-form', ['course' => $course, 'session' => new CourseSession]);
    }

    public function store(Request $request, Course $course, BrandContext $context, AuditService $audit)
    {
        $course = $this->course($course, $context);
        $this->authorize('create', CourseSession::class);
        [$data, $plans] = $this->validated($request);
        $session = DB::transaction(function () use ($course, $context, $data, $plans) {
            $session = $course->sessions()->create($data + ['brand_id' => $context->id()]);
            $this->syncPlans($session, $course, $plans, $context->id());
            return $session;
        });
        $audit->record('course_sessions.created', $session, [], $session->load('plans')->toArray());
        return redirect()->route('admin.courses.edit', $course)->with('status', '場次已建立。');
    }

    public function edit(Course $course, CourseSession $session, BrandContext $context)
    {
        $course = $this->course($course, $context);
        $session = $this->session($course, $session, $context);
        return view('admin.courses.session-form', compact('course', 'session'));
    }

    public function update(Request $request, Course $course, CourseSession $session, BrandContext $context, AuditService $audit)
    {
        $course = $this->course($course, $context);
        $session = $this->session($course, $session, $context);
        $this->authorize('update', $session);
        [$data, $plans] = $this->validated($request);
        if ($session->status === 'open' && (int) $data['capacity'] !== $session->capacity) {
            return back()->withErrors(['capacity' => '場次開放報名後不可調整名額。'])->withInput();
        }
        $before = $session->load('plans')->toArray();
        DB::transaction(function () use ($session, $course, $data, $plans, $context) {
            $session->update($data);
            $this->syncPlans($session, $course, $plans, $context->id());
        });
        $audit->record('course_sessions.updated', $session, $before, $session->fresh()->load('plans')->toArray());
        return back()->with('status', '場次已更新。');
    }

    private function course(Course $course, BrandContext $context): Course
    {
        abort_unless($course->brand_id === $context->id(), 404);
        $this->authorize('view', $course);
        return $course;
    }

    private function session(Course $course, CourseSession $session, BrandContext $context): CourseSession
    {
        abort_unless($session->brand_id === $context->id() && $session->course_id === $course->id, 404);
        $this->authorize('view', $session);
        return $session;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at',
            'city' => 'required|string|max:50', 'venue_name' => 'required|string|max:160',
            'address' => 'required|string|max:500',
            'google_maps_url' => ['required', 'url:http,https', 'max:2000'],
            'capacity' => 'required|integer|min:1|max:10000',
            'registration_close_days' => 'nullable|integer|min:0|max:365',
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'cancelled'])],
            'override_plans' => 'nullable|boolean', 'plans' => 'nullable|array|max:12',
            'plans.*.name' => 'required_with:override_plans|string|max:100',
            'plans.*.participants' => 'required_with:override_plans|integer|min:1|max:100',
            'plans.*.price' => 'required_with:override_plans|numeric|min:0|max:99999999',
            'plans.*.is_enabled' => 'nullable|boolean',
        ]);
        $plans = $request->boolean('override_plans') ? ($data['plans'] ?? []) : [];
        unset($data['plans'], $data['override_plans']);
        return [$data, $plans];
    }

    private function syncPlans(CourseSession $session, Course $course, array $plans, int $brandId): void
    {
        $session->plans()->delete();
        foreach (array_values($plans) as $index => $plan) {
            $session->plans()->create([
                'brand_id' => $brandId, 'course_id' => $course->id,
                'name' => $plan['name'], 'participants' => $plan['participants'],
                'price' => $plan['price'], 'sort_order' => $index,
                'is_enabled' => (bool) ($plan['is_enabled'] ?? false),
            ]);
        }
    }
}
