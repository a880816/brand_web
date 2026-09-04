<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('brands', function (Blueprint $table) {
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('home_menu_label')->default('首頁');
            $table->string('courses_menu_label')->default('手作課程');
            $table->string('shop_menu_label')->default('線上商店');
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 20)->nullable();
            $table->string('bank_branch')->nullable();
            $table->string('bank_account_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->text('remittance_notice')->nullable();
        });

        Schema::create('homepage_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->unique()->constrained()->cascadeOnDelete();
            $table->jsonb('draft_data')->default('{}');
            $table->jsonb('published_data')->default('{}');
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->text('suitable_for')->nullable();
            $table->text('precautions')->nullable();
            $table->text('notion_url')->nullable();
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('city', 50);
            $table->string('venue_name');
            $table->string('address');
            $table->text('google_maps_url');
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('registration_close_days')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['brand_id', 'starts_at']);
        });

        Schema::create('course_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_session_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedInteger('participants');
            $table->decimal('price', 10, 2);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('course_registrations', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_session_id')->constrained()->restrictOnDelete();
            $table->foreignId('course_plan_id')->constrained()->restrictOnDelete();
            $table->string('contact_name');
            $table->string('phone', 40);
            $table->string('email');
            $table->string('social_platform', 30);
            $table->string('social_account');
            $table->unsignedInteger('participants');
            $table->string('plan_name');
            $table->decimal('amount', 10, 2);
            $table->text('notes')->nullable();
            $table->string('remittance_last_five', 5)->nullable();
            $table->string('status')->default('awaiting_payment')->index();
            $table->timestampTz('payment_due_at');
            $table->timestampTz('confirmed_at')->nullable();
            $table->timestampTz('cancelled_at')->nullable();
            $table->text('admin_note')->nullable();
            $table->jsonb('bank_snapshot');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['brand_id', 'course_session_id', 'status']);
        });

        Schema::create('plant_varieties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('scientific_name');
            $table->string('variety_code');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->text('care_instructions')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestampTz('published_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['brand_id', 'variety_code']);
            $table->unique(['brand_id', 'slug']);
        });

        Schema::create('plant_specimens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plant_variety_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('sequence');
            $table->string('custom_name')->nullable();
            $table->string('full_tag_name')->unique();
            $table->text('description')->nullable();
            $table->jsonb('specifications')->default('[]');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock_on_hand')->default(1);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('sold_sequence')->default(0);
            $table->string('status')->default('draft')->index();
            $table->timestampTz('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['plant_variety_id', 'sequence']);
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->string('product_code');
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->jsonb('specifications')->default('[]');
            $table->decimal('price', 10, 2);
            $table->unsignedInteger('stock_on_hand')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->string('status')->default('draft')->index();
            $table->timestampTz('published_at')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['brand_id', 'product_code']);
            $table->unique(['brand_id', 'slug']);
        });

        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->uuid('reference')->unique();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('draft')->index();
            $table->string('customer_name')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('social_platform', 30)->nullable();
            $table->string('social_account')->nullable();
            $table->string('shipping_method', 30)->nullable();
            $table->jsonb('shipping_details')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('shipping_fee', 10, 2)->default(0);
            $table->boolean('free_shipping')->default(false);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('remittance_last_five', 5)->nullable();
            $table->text('notes')->nullable();
            $table->jsonb('bank_snapshot');
            $table->string('recipient_token_hash', 64)->nullable()->unique();
            $table->timestampTz('recipient_link_expires_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['brand_id', 'status', 'created_at']);
        });

        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
            $table->string('product_type', 30);
            $table->unsignedBigInteger('product_id');
            $table->string('product_code');
            $table->string('product_name');
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->decimal('line_total', 10, 2);
            $table->unsignedInteger('fulfilled_quantity')->default(0);
            $table->jsonb('snapshot')->default('{}');
            $table->timestamps();
            $table->index(['product_type', 'product_id']);
        });

        Schema::create('plant_sold_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plant_variety_id')->constrained()->restrictOnDelete();
            $table->foreignId('plant_specimen_id')->constrained()->restrictOnDelete();
            $table->foreignId('sale_order_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('unit_sequence');
            $table->string('sold_code');
            $table->string('full_tag_name_snapshot')->unique();
            $table->string('variety_name_snapshot');
            $table->decimal('price_snapshot', 10, 2);
            $table->timestampTz('sold_at');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['plant_specimen_id', 'unit_sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_sold_units');
        Schema::dropIfExists('sale_order_items');
        Schema::dropIfExists('sale_orders');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('plant_specimens');
        Schema::dropIfExists('plant_varieties');
        Schema::dropIfExists('course_registrations');
        Schema::dropIfExists('course_plans');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('homepage_contents');

        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn(['suitable_for', 'precautions', 'notion_url']);
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropColumn([
                'facebook_url', 'instagram_url', 'home_menu_label', 'courses_menu_label',
                'shop_menu_label', 'bank_name', 'bank_code', 'bank_branch',
                'bank_account_name', 'bank_account_number', 'remittance_notice',
            ]);
        });
    }
};
