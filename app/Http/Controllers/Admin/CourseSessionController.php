<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSession;
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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
        [$data, $plans] = $this->validated($request, $course, $context);
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
        [$data, $plans] = $this->validated($request, $course, $context, $session);
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

    public function destroy(Course $course, CourseSession $session, BrandContext $context, AuditService $audit)
    {
        $course = $this->course($course, $context);
        $session = $this->session($course, $session, $context);
        $this->authorize('delete', $session);
        abort_if($session->registrations()->exists(), 422, '已有報名紀錄的場次不可刪除，請改為取消場次。');
        $audit->record('course_sessions.deleted', $session, $session->load('plans')->toArray());
        $session->delete();

        return redirect()->route('admin.courses.edit', $course)->with('status', '場次已刪除。');
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

    private function validated(Request $request, Course $course, BrandContext $context, ?CourseSession $session = null): array
    {
        $data = $request->validate([
            'starts_at' => 'required|date', 'ends_at' => 'required|date|after:starts_at',
            'city' => 'required|string|max:50', 'venue_name' => 'required|string|max:160',
            'address' => 'required|string|max:500',
            'google_maps_url' => ['required', 'url:https', 'max:2000', function (string $attribute, mixed $value, \Closure $fail) {
                $host = strtolower((string) parse_url($value, PHP_URL_HOST));
                if (! in_array($host, ['maps.google.com', 'www.google.com', 'maps.app.goo.gl'], true) && ! str_ends_with($host, '.google.com')) {
                    $fail('Google Maps 連結網域不正確。');
                }
            }],
            'capacity' => 'required|integer|min:1|max:10000',
            'registration_close_days' => 'nullable|integer|min:0|max:365',
            'status' => ['required', Rule::in(['draft', 'open', 'closed', 'cancelled'])],
            'override_plans' => 'nullable|boolean', 'plans' => 'nullable|array|max:12',
            'plans.*.id' => [
                'nullable', 'integer', 'distinct',
                Rule::exists('course_plans', 'id')->where(fn ($query) => $query
                    ->where('brand_id', $context->id())
                    ->where('course_id', $course->id)
                    ->where('course_session_id', $session?->id ?? 0)),
            ],
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
        $retainedIds = [];

        foreach (array_values($plans) as $index => $plan) {
            $attributes = [
                'brand_id' => $brandId, 'course_id' => $course->id,
                'name' => $plan['name'], 'participants' => $plan['participants'],
                'price' => $plan['price'], 'sort_order' => $index,
                'is_enabled' => (bool) ($plan['is_enabled'] ?? false),
            ];

            if (filled($plan['id'] ?? null)) {
                $existing = $session->plans()->findOrFail((int) $plan['id']);
                $existing->update($attributes);
                $retainedIds[] = $existing->id;
            } else {
                $retainedIds[] = $session->plans()->create($attributes)->id;
            }
        }

        $removedPlans = $session->plans()->whereNotIn('id', $retainedIds)->get();
        if ($removedPlans->contains(fn ($plan) => $plan->registrations()->exists())) {
            throw ValidationException::withMessages([
                'plans' => '已有報名紀錄的場次方案不可移除，請改為停用。',
            ]);
        }

        $removedPlans->each->delete();
    }
}
