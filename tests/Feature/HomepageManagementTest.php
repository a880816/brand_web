<?php

namespace Tests\Feature;

use App\Models\{Brand, Course, HomepageContent, User};
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageManagementTest extends TestCase
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

    public function test_draft_preview_and_publish_are_separate(): void
    {
        [$brand, $admin] = $this->context();
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/homepage', ['hero_title' => '尚未發布標題'])
            ->assertRedirect();

        $this->get('http://brand-a.localhost/')->assertDontSee('尚未發布標題');
        $this->actingAs($admin)->get('http://brand-a.localhost/admin/homepage/preview')
            ->assertOk()->assertSee('草稿預覽')->assertSee('尚未發布標題');
        $this->actingAs($admin)->post('http://brand-a.localhost/admin/homepage/publish')->assertRedirect();
        $this->get('http://brand-a.localhost/')->assertOk()->assertSee('尚未發布標題');
        $this->assertNotNull(HomepageContent::first()->published_at);
    }

    public function test_featured_courses_cannot_cross_brand_or_exceed_six(): void
    {
        [$brand, $admin] = $this->context();
        $foreignBrand = Brand::factory()->create(['slug' => 'terracotta', 'domain' => 'brand-b.localhost']);
        $foreignCourse = Course::factory()->create(['brand_id' => $foreignBrand->id]);

        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/homepage', ['featured_course_ids' => [$foreignCourse->id]])
            ->assertSessionHasErrors('featured_course_ids.0');

        $courses = Course::factory()->count(7)->create(['brand_id' => $brand->id]);
        $this->actingAs($admin)->withServerVariables(['HTTP_HOST' => 'brand-a.localhost'])
            ->put('/admin/homepage', ['featured_course_ids' => $courses->pluck('id')->all()])
            ->assertSessionHasErrors('featured_course_ids');
    }

    public function test_each_known_brand_uses_an_independent_home_template(): void
    {
        $a = Brand::factory()->create(['slug' => 'verdant', 'domain' => 'brand-a.localhost']);
        $b = Brand::factory()->create(['slug' => 'terracotta', 'domain' => 'brand-b.localhost']);
        $a->homepageContent()->create(['published_data' => ['hero_title' => 'Brand A'], 'draft_data' => []]);
        $b->homepageContent()->create(['published_data' => ['hero_title' => 'Brand B'], 'draft_data' => []]);

        $this->get('http://brand-a.localhost/')->assertSee('brand-home-verdant')->assertSee('>Brand A<', false)->assertDontSee('>Brand B<', false);
        $this->get('http://brand-b.localhost/')->assertSee('brand-home-terracotta')->assertSee('>Brand B<', false)->assertDontSee('>Brand A<', false);
    }
}
