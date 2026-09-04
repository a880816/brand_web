<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, CourseSession, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $closed = $this->course($brand); $closed->update(['name' => '無場次課程', 'slug' => 'closed', 'sort_order' => 0]);
        $open = $this->course($brand); $open->update(['name' => '可報名課程', 'slug' => 'open', 'sort_order' => 10]);
        $open->sessions()->create($this->payload() + ['brand_id' => $brand->id]);
        $old = $this->course($brand); $old->update(['name' => '過期課程', 'slug' => 'old']);
        $old->sessions()->create($this->payload(['starts_at' => now()->subMonths(3), 'ends_at' => now()->subMonths(3)->addHour(), 'status' => 'closed']) + ['brand_id' => $brand->id]);

        $response = $this->get('http://brand-a.localhost/courses')->assertOk()->assertDontSee('過期課程');
        $this->assertLessThan(strpos($response->getContent(), '無場次課程'), strpos($response->getContent(), '可報名課程'));
        $this->get('http://brand-a.localhost/courses/open')->assertOk()->assertSee('選擇課程場次');
    }
}
