<?php

namespace App\Http\Controllers;

use App\Models\{Course, CourseRegistration, CourseSession};
use App\Services\CourseRegistrationService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseRegistrationController extends Controller
{
    public function create(string $slug, CourseSession $session, BrandContext $context)
    {
        $course = $this->courseAndSession($slug, $session, $context);
        abort_unless($session->availabilityStatus() === 'open', 404);
        $plans = $session->effectivePlans()->where('is_enabled', true);
        return view('site.courses.register', compact('course', 'session', 'plans'));
    }

    public function store(Request $request, string $slug, CourseSession $session, BrandContext $context, CourseRegistrationService $service)
    {
        $course = $this->courseAndSession($slug, $session, $context);
        $data = $request->validate([
            'course_plan_id' => 'required|integer', 'contact_name' => 'required|string|max:100',
            'phone' => 'required|string|max:40', 'email' => 'required|email|max:255',
            'social_platform' => ['required', Rule::in(['facebook', 'instagram', 'line', 'other'])],
            'social_account' => 'required|string|max:255', 'notes' => 'nullable|string|max:2000',
            'remittance_last_five' => ['nullable', 'regex:/^[0-9]{5}$/'],
            'company_website' => 'prohibited',
        ]);
        $registration = $service->register($context->brand(), $session, (int) $data['course_plan_id'], $data);
        return redirect()->route('registrations.show', $registration->reference);
    }

    public function show(string $reference, BrandContext $context)
    {
        $registration = CourseRegistration::where('brand_id', $context->id())->where('reference', $reference)
            ->with(['course', 'session'])->firstOrFail();
        return view('site.courses.registration-result', compact('registration'));
    }

    private function courseAndSession(string $slug, CourseSession $session, BrandContext $context): Course
    {
        $course = Course::where('brand_id', $context->id())->published()->where('slug', $slug)->firstOrFail();
        abort_unless($session->brand_id === $context->id() && $session->course_id === $course->id, 404);
        $session->load(['course.plans', 'plans', 'registrations']);
        return $course;
    }
}
