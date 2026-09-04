<?php

namespace App\Services;

use App\Models\{Brand, CoursePlan, CourseRegistration, CourseSession};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CourseRegistrationService
{
    public function register(Brand $brand, CourseSession $requestedSession, int $planId, array $contact): CourseRegistration
    {
        return DB::transaction(function () use ($brand, $requestedSession, $planId, $contact) {
            $session = CourseSession::where('brand_id', $brand->id)->lockForUpdate()->findOrFail($requestedSession->id);
            $session->load('course');
            abort_unless($session->course->status === 'published' && ($session->course->published_at === null || $session->course->published_at->lte(now())), 404);

            if ($session->availabilityStatus() !== 'open') {
                throw ValidationException::withMessages(['session' => '此場次目前無法報名。']);
            }

            $usesOverrides = CoursePlan::where('course_session_id', $session->id)->exists();
            $plan = CoursePlan::where('brand_id', $brand->id)
                ->where('course_id', $session->course_id)
                ->where('is_enabled', true)
                ->where('course_session_id', $usesOverrides ? $session->id : null)
                ->find($planId);
            if (! $plan) {
                throw ValidationException::withMessages(['course_plan_id' => '所選方案不存在或已停用。']);
            }
            if ($session->remainingCapacity() < $plan->participants) {
                throw ValidationException::withMessages(['course_plan_id' => '剩餘名額不足，請改選其他方案或場次。']);
            }

            $lastFive = $contact['remittance_last_five'] ?? null;
            return CourseRegistration::create([
                'reference' => (string) Str::uuid(), 'brand_id' => $brand->id,
                'course_id' => $session->course_id, 'course_session_id' => $session->id,
                'course_plan_id' => $plan->id, 'contact_name' => $contact['contact_name'],
                'phone' => $contact['phone'], 'email' => $contact['email'],
                'social_platform' => $contact['social_platform'], 'social_account' => $contact['social_account'],
                'participants' => $plan->participants, 'plan_name' => $plan->name,
                'amount' => $plan->price, 'notes' => $contact['notes'] ?? null,
                'remittance_last_five' => $lastFive,
                'status' => $lastFive ? 'awaiting_review' : 'awaiting_payment',
                'payment_due_at' => now()->addHours(config('courses.payment_due_hours')),
                'bank_snapshot' => $brand->bankSnapshot(),
            ]);
        }, 3);
    }
}
