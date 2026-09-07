<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    private function context(): array
    {
        $brand = Brand::factory()->create(['slug' => 'verdant', 'domain' => 'brand-a.localhost']);
        $admin = User::factory()->brandAdmin()->create();
        $admin->brands()->attach($brand);

        return [$brand, $admin];
    }

    private function course(Brand $brand): Course
    {
        $course = Course::factory()->create(['brand_id' => $brand->id, 'status' => 'published', 'published_at' => now()]);
        $course->plans()->create(['brand_id' => $brand->id, 'name' => '雙人方案', 'participants' => 2, 'price' => 2000, 'is_enabled' => true]);

        return $course;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'starts_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'ends_at' => now()->addDays(10)->addHours(2)->format('Y-m-d H:i:s'),
            'city' => '台北市', 'venue_name' => '植感工作室', 'address' => '中山路 1 號',
            'google_maps_url' => 'https://maps.google.com/?q=taipei', 'capacity' => 8,
            'registration_close_days' => 3, 'status' => 'open',
        ], $overrides);
    }

    public function test_admin_can_create_course_with_fixed_price_plans(): void
    {
        [$brand, $admin] = $this->context();
        $response = $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])->post('/admin/courses', [
            'name' => '鹿角蕨上板', 'slug' => 'mounting', 'summary' => '入門課程',
            'plans' => [['name' => '雙人方案', 'participants' => 2, 'price' => 2000, 'is_enabled' => 1]],
        ]);
        $response->assertRedirect();
        $course = Course::first();
        $this->assertSame($brand->id, $course->brand_id);
        $this->assertDatabaseHas('course_plans', ['course_id' => $course->id, 'participants' => 2, 'price' => 2000]);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->get('/admin/courses/'.$course->id.'/edit')->assertOk()->assertSee('預設方案');
    }

    public function test_adding_course_plan_preserves_plan_referenced_by_registration(): void
    {
        [$brand, $admin] = $this->context();
        $course = $this->course($brand);
        $plan = $course->plans()->first();
        $session = $course->sessions()->create($this->payload() + ['brand_id' => $brand->id]);
        $registration = $session->registrations()->create([
            'reference' => (string) Str::uuid(),
            'brand_id' => $brand->id,
            'course_id' => $course->id,
            'course_plan_id' => $plan->id,
            'contact_name' => '客人',
            'phone' => '0912345678',
            'email' => 'guest@example.test',
            'social_platform' => 'line',
            'social_account' => 'guest',
            'participants' => $plan->participants,
            'plan_name' => $plan->name,
            'amount' => $plan->price,
            'status' => 'awaiting_payment',
            'payment_due_at' => now()->addDay(),
            'bank_snapshot' => [],
        ]);

        $response = $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/courses/'.$course->id, [
                'name' => $course->name,
                'slug' => $course->slug,
                'plans' => [
                    ['id' => $plan->id, 'name' => '雙人方案', 'participants' => 2, 'price' => 2000, 'is_enabled' => 1],
                    ['name' => '四人方案', 'participants' => 4, 'price' => 3600, 'is_enabled' => 1],
                ],
            ]);

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('course_plans', ['id' => $plan->id, 'course_id' => $course->id]);
        $this->assertDatabaseHas('course_plans', ['course_id' => $course->id, 'name' => '四人方案']);
        $this->assertSame($plan->id, $registration->fresh()->course_plan_id);

        $registration->delete();
        $newPlan = $course->plans()->where('name', '四人方案')->firstOrFail();
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/courses/'.$course->id, [
                'name' => $course->name,
                'slug' => $course->slug,
                'plans' => [
                    ['id' => $newPlan->id, 'name' => $newPlan->name, 'participants' => 4, 'price' => 3600, 'is_enabled' => 1],
                ],
            ])->assertSessionHasErrors('plans');

        $this->assertDatabaseHas('course_plans', ['id' => $plan->id]);
        $this->assertDatabaseCount('course_plans', 2);
    }

    public function test_adding_session_plan_preserves_plan_referenced_by_registration(): void
    {
        [$brand, $admin] = $this->context();
        $course = $this->course($brand);
        $session = $course->sessions()->create($this->payload(['status' => 'draft']) + ['brand_id' => $brand->id]);
        $plan = $session->plans()->create([
            'brand_id' => $brand->id,
            'course_id' => $course->id,
            'name' => '場次雙人方案',
            'participants' => 2,
            'price' => 2200,
            'is_enabled' => true,
        ]);
        $session->registrations()->create([
            'reference' => (string) Str::uuid(),
            'brand_id' => $brand->id,
            'course_id' => $course->id,
            'course_plan_id' => $plan->id,
            'contact_name' => '客人',
            'phone' => '0912345678',
            'email' => 'guest@example.test',
            'social_platform' => 'line',
            'social_account' => 'guest',
            'participants' => $plan->participants,
            'plan_name' => $plan->name,
            'amount' => $plan->price,
            'status' => 'awaiting_payment',
            'payment_due_at' => now()->addDay(),
            'bank_snapshot' => [],
        ]);

        $response = $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/courses/'.$course->id.'/sessions/'.$session->id, $this->payload([
                'status' => 'draft',
                'override_plans' => 1,
                'plans' => [
                    ['id' => $plan->id, 'name' => '場次雙人方案', 'participants' => 2, 'price' => 2200, 'is_enabled' => 1],
                    ['name' => '場次四人方案', 'participants' => 4, 'price' => 4000, 'is_enabled' => 1],
                ],
            ]));

        $response->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('course_plans', ['id' => $plan->id, 'course_session_id' => $session->id]);
        $this->assertDatabaseHas('course_plans', ['course_session_id' => $session->id, 'name' => '場次四人方案']);
    }

    public function test_open_session_capacity_is_locked_and_cross_brand_session_is_hidden(): void
    {
        [$brand, $admin] = $this->context();
        $course = $this->course($brand);
        $session = $course->sessions()->create($this->payload() + ['brand_id' => $brand->id]);

        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put("/admin/courses/{$course->id}/sessions/{$session->id}", $this->payload(['capacity' => 10]))
            ->assertSessionHasErrors('capacity');
        $this->assertSame(8, $session->fresh()->capacity);

        $foreign = Brand::factory()->create(['slug' => 'other', 'domain' => 'other.localhost']);
        $foreignCourse = $this->course($foreign);
        $foreignSession = $foreignCourse->sessions()->create($this->payload() + ['brand_id' => $foreign->id]);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->get("/admin/courses/{$foreignCourse->id}/sessions/{$foreignSession->id}/edit")->assertNotFound();
    }

    public function test_frontend_sorts_available_courses_first_and_hides_old_courses(): void
    {
        [$brand] = $this->context();
        $closed = $this->course($brand);
        $closed->update(['name' => '無場次課程', 'slug' => 'closed', 'sort_order' => 0]);
        $open = $this->course($brand);
        $open->update(['name' => '可報名課程', 'slug' => 'open', 'sort_order' => 10]);
        $open->sessions()->create($this->payload() + ['brand_id' => $brand->id]);
        $old = $this->course($brand);
        $old->update(['name' => '過期課程', 'slug' => 'old']);
        $old->sessions()->create($this->payload(['starts_at' => now()->subMonths(3), 'ends_at' => now()->subMonths(3)->addHour(), 'status' => 'closed']) + ['brand_id' => $brand->id]);

        $response = $this->get('http://brand-a.localhost/courses')->assertOk()->assertDontSee('過期課程');
        $this->assertLessThan(strpos($response->getContent(), '無場次課程'), strpos($response->getContent(), '可報名課程'));
        $this->get('http://brand-a.localhost/courses/open')->assertOk()->assertSee('選擇課程場次');
    }

    public function test_external_course_links_use_host_allowlists(): void
    {
        [$brand, $admin] = $this->context();
        $course = $this->course($brand);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->post("/admin/courses/{$course->id}/sessions", $this->payload(['google_maps_url' => 'https://evil.example/map']))
            ->assertSessionHasErrors('google_maps_url');
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put("/admin/courses/{$course->id}", [
                'name' => $course->name, 'slug' => $course->slug, 'notion_url' => 'https://evil.example/notice',
                'plans' => [['name' => '雙人方案', 'participants' => 2, 'price' => 2000, 'is_enabled' => 1]],
            ])->assertSessionHasErrors('notion_url');
    }

    public function test_empty_session_can_be_deleted_but_registered_session_is_retained(): void
    {
        [$brand,$admin] = $this->context();
        $course = $this->course($brand);
        $empty = $course->sessions()->create($this->payload(['status' => 'draft']) + ['brand_id' => $brand->id]);
        $client = $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost']);
        $client->delete("/admin/courses/{$course->id}/sessions/{$empty->id}")->assertRedirect();
        $this->assertSoftDeleted('course_sessions', ['id' => $empty->id]);

        $registered = $course->sessions()->create($this->payload() + ['brand_id' => $brand->id]);
        $plan = $course->plans()->first();
        $registered->registrations()->create(['reference' => (string) Str::uuid(), 'brand_id' => $brand->id, 'course_id' => $course->id, 'course_plan_id' => $plan->id, 'contact_name' => '客人', 'phone' => '0912345678', 'email' => 'guest@example.test', 'social_platform' => 'line', 'social_account' => 'guest', 'participants' => $plan->participants, 'plan_name' => $plan->name, 'amount' => $plan->price, 'status' => 'awaiting_payment', 'payment_due_at' => now()->addDay(), 'bank_snapshot' => []]);
        $client->delete("/admin/courses/{$course->id}/sessions/{$registered->id}")->assertStatus(422);
        $this->assertDatabaseHas('course_sessions', ['id' => $registered->id, 'deleted_at' => null]);
    }

    public function test_registered_course_delete_redirects_back_with_actionable_error(): void
    {
        [$brand, $admin] = $this->context();
        $course = $this->course($brand);
        $plan = $course->plans()->first();
        $session = $course->sessions()->create($this->payload() + ['brand_id' => $brand->id]);
        $session->registrations()->create([
            'reference' => (string) Str::uuid(),
            'brand_id' => $brand->id,
            'course_id' => $course->id,
            'course_plan_id' => $plan->id,
            'contact_name' => '客人',
            'phone' => '0912345678',
            'email' => 'guest@example.test',
            'social_platform' => 'line',
            'social_account' => 'guest',
            'participants' => $plan->participants,
            'plan_name' => $plan->name,
            'amount' => $plan->price,
            'status' => 'awaiting_payment',
            'payment_due_at' => now()->addDay(),
            'bank_snapshot' => [],
        ]);

        $editUrl = '/admin/courses/'.$course->id.'/edit';
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->from($editUrl)
            ->delete('/admin/courses/'.$course->id)
            ->assertRedirect($editUrl)
            ->assertSessionHasErrors([
                'course' => '已有報名紀錄的課程不可刪除，請改為下架。',
            ]);

        $this->assertNotSoftDeleted('courses', ['id' => $course->id]);
    }
}
