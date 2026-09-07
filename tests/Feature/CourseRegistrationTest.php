<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, CourseRegistration, CourseSession, User};
use App\Services\CourseRegistrationService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class CourseRegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([ValidateCsrfToken::class, \Illuminate\Routing\Middleware\ThrottleRequests::class]);
        Carbon::setTestNow('2026-09-04 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function fixture(int $capacity = 4): array
    {
        $brand = Brand::factory()->create([
            'slug' => 'verdant', 'domain' => 'brand-a.localhost',
            'bank_name' => '測試銀行', 'bank_code' => '999', 'bank_account_number' => '12345678',
        ]);
        $course = Course::factory()->create(['brand_id' => $brand->id, 'slug' => 'workshop', 'status' => 'published', 'published_at' => now(), 'notion_url' => 'https://example.notion.site/before-class']);
        $plan = $course->plans()->create(['brand_id' => $brand->id, 'name' => '雙人方案', 'participants' => 2, 'price' => 2000, 'is_enabled' => true]);
        $session = $course->sessions()->create([
            'brand_id' => $brand->id, 'starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(2),
            'city' => '台北市', 'venue_name' => '工作室', 'address' => '地址', 'google_maps_url' => 'https://maps.google.com',
            'capacity' => $capacity, 'registration_close_days' => 3, 'status' => 'open',
        ]);
        return [$brand, $course, $session, $plan];
    }

    private function payload(int $planId, array $overrides = []): array
    {
        return array_merge([
            'course_plan_id' => $planId, 'contact_name' => '王小明', 'phone' => '0912345678',
            'email' => 'guest@example.com', 'social_platform' => 'instagram', 'social_account' => '@guest',
        ], $overrides);
    }

    public function test_guest_can_register_without_account_and_capacity_is_reserved(): void
    {
        [, $course, $session, $plan] = $this->fixture();
        $response = $this->get("http://brand-a.localhost/courses/{$course->slug}/sessions/{$session->id}/register")
            ->assertOk()->assertSee('本頁不要求註冊會員');
        $this->post("http://brand-a.localhost/courses/{$course->slug}/sessions/{$session->id}/register", $this->payload($plan->id))
            ->assertRedirect();
        $registration = CourseRegistration::first();
        $this->assertSame(2, $registration->participants);
        $this->assertSame('awaiting_payment', $registration->status);
        $this->assertTrue($registration->payment_due_at->equalTo(now()->addHours(24)));
        $this->assertSame(2, $session->fresh()->remainingCapacity());
        $this->get('http://brand-a.localhost/registrations/'.$registration->reference)->assertOk()->assertSee('NT$ 2,000')->assertSee('測試銀行');
        $this->get('http://brand-a.localhost/course-notice/workshop/qr.svg')->assertOk()->assertHeader('Content-Type', 'image/svg+xml')->assertSee('<svg', false);
    }

    public function test_last_five_is_optional_and_moves_registration_to_review(): void
    {
        [, $course, $session, $plan] = $this->fixture();
        $this->post("http://brand-a.localhost/courses/{$course->slug}/sessions/{$session->id}/register", $this->payload($plan->id, ['remittance_last_five' => '12345']))->assertRedirect();
        $this->assertDatabaseHas('course_registrations', ['remittance_last_five' => '12345', 'status' => 'awaiting_review']);
    }

    public function test_capacity_and_brand_are_verified_inside_registration_transaction(): void
    {
        [$brand, $course, $session, $plan] = $this->fixture(2);
        app(CourseRegistrationService::class)->register($brand, $session, $plan->id, $this->payload($plan->id));
        $this->post("http://brand-a.localhost/courses/{$course->slug}/sessions/{$session->id}/register", $this->payload($plan->id, ['email' => 'second@example.com']))
            ->assertSessionHasErrors('session');

        $other = Brand::factory()->create(['slug' => 'other', 'domain' => 'other.localhost']);
        $this->get("http://other.localhost/courses/{$course->slug}/sessions/{$session->id}/register")->assertNotFound();
        $this->assertSame(1, CourseRegistration::count());
    }

    public function test_admin_cancellation_releases_capacity_and_duplicate_last_five_only_warns(): void
    {
        [$brand, , $session, $plan] = $this->fixture();
        $service = app(CourseRegistrationService::class);
        $one = $service->register($brand, $session, $plan->id, $this->payload($plan->id, ['remittance_last_five' => '12345']));
        $two = $service->register($brand, $session, $plan->id, $this->payload($plan->id, ['email' => 'two@example.com']));
        $admin = User::factory()->brandAdmin()->create(); $admin->brands()->attach($brand);

        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/registrations/'.$two->id, ['status' => 'cancelled', 'remittance_last_five' => '12345', 'admin_note' => '客人取消'])
            ->assertRedirect()->assertSessionHas('duplicate_last_five', true);
        $this->assertSame('cancelled', $two->fresh()->status);
        $this->assertSame(2, $session->fresh()->remainingCapacity());
        $this->assertNotNull($one->fresh());
    }

    public function test_registration_honeypot_is_rejected(): void
    {
        [, $course, $session, $plan] = $this->fixture();
        $this->post("http://brand-a.localhost/courses/{$course->slug}/sessions/{$session->id}/register", $this->payload($plan->id, ['company_website'=>'spam.example']))
            ->assertSessionHasErrors('company_website');
        $this->assertDatabaseCount('course_registrations', 0);
    }
}
