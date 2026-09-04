<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CommerceDataModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_course_shop_and_order_tables_are_available(): void
    {
        foreach ([
            'homepage_contents', 'course_sessions', 'course_plans', 'course_registrations',
            'plant_varieties', 'plant_specimens', 'materials', 'sale_orders',
            'sale_order_items', 'plant_sold_units',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), $table.' should exist');
        }

        $this->assertSame(3, config('courses.registration_close_days'));
        $this->assertSame(80, config('commerce.shipping_fees.seven_eleven'));
        $this->assertSame(168, config('commerce.recipient_link_ttl_hours'));
    }
}
