<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseRegistration;
use App\Services\AuditService;
use App\Support\BrandContext;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CourseRegistrationController extends Controller
{
    public function index(BrandContext $context)
    {
        $this->authorize('viewAny', CourseRegistration::class);
        $registrations = CourseRegistration::where('brand_id', $context->id())
            ->with(['course', 'session'])->latest()->paginate(30);
        return view('admin.registrations.index', compact('registrations'));
    }

    public function edit(CourseRegistration $registration, BrandContext $context)
    {
        $registration = $this->registration($registration, $context);
        return view('admin.registrations.edit', compact('registration'));
    }

    public function update(Request $request, CourseRegistration $registration, BrandContext $context, AuditService $audit)
    {
        $registration = $this->registration($registration, $context);
        $this->authorize('update', $registration);
        $data = $request->validate([
            'status' => ['required', Rule::in(['awaiting_payment', 'awaiting_review', 'confirmed', 'cancelled'])],
            'remittance_last_five' => ['nullable', 'regex:/^[0-9]{5}$/'],
            'admin_note' => 'nullable|string|max:2000',
        ]);
        $duplicate = null;
        if (filled($data['remittance_last_five'])) {
            $duplicate = CourseRegistration::where('brand_id', $context->id())
                ->where('remittance_last_five', $data['remittance_last_five'])->whereKeyNot($registration->id)->exists();
        }
        $before = $registration->toArray();
        $data['confirmed_at'] = $data['status'] === 'confirmed' ? ($registration->confirmed_at ?? now()) : null;
        $data['cancelled_at'] = $data['status'] === 'cancelled' ? ($registration->cancelled_at ?? now()) : null;
        $registration->update($data);
        $audit->record($data['status'] === 'cancelled' ? 'course_registrations.cancelled' : 'course_registrations.updated', $registration, $before, $registration->fresh()->toArray());
        return back()->with('status', '報名資料已更新。')->with('duplicate_last_five', $duplicate);
    }

    private function registration(CourseRegistration $registration, BrandContext $context): CourseRegistration
    {
        abort_unless($registration->brand_id === $context->id(), 404);
        $this->authorize('view', $registration);
        return $registration->load(['course', 'session']);
    }
}
