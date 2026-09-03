<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('slug')->unique(); $table->string('domain')->nullable()->unique();
            $table->string('status')->default('active'); $table->string('primary_color', 20); $table->string('secondary_color', 20);
            $table->string('accent_color', 20); $table->string('background_color', 20); $table->string('text_color', 20);
            $table->jsonb('theme_settings')->default('{}'); $table->string('seo_title')->nullable(); $table->text('seo_description')->nullable();
            $table->timestamps(); $table->softDeletes();
        });
        Schema::create('pages', function (Blueprint $table) {
            $table->id(); $table->foreignId('brand_id')->constrained()->cascadeOnDelete(); $table->string('type'); $table->string('title'); $table->string('slug');
            $table->text('excerpt')->nullable(); $table->text('body')->nullable(); $table->string('status')->default('draft'); $table->timestampTz('published_at')->nullable();
            $table->string('seo_title')->nullable(); $table->text('seo_description')->nullable(); $table->timestamps(); $table->softDeletes(); $table->unique(['brand_id','slug']);
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id(); $table->foreignId('brand_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->string('slug');
            $table->text('summary')->nullable(); $table->text('description')->nullable(); $table->integer('sort_order')->default(0); $table->string('status')->default('draft');
            $table->timestampTz('published_at')->nullable(); $table->string('seo_title')->nullable(); $table->text('seo_description')->nullable(); $table->timestamps(); $table->softDeletes(); $table->unique(['brand_id','slug']);
        });
        Schema::create('courses', function (Blueprint $table) {
            $table->id(); $table->foreignId('brand_id')->constrained()->cascadeOnDelete(); $table->string('name'); $table->string('slug');
            $table->text('summary')->nullable(); $table->text('description')->nullable(); $table->string('location_note')->nullable(); $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('price_amount', 10, 2)->nullable(); $table->string('price_note')->nullable(); $table->integer('sort_order')->default(0); $table->string('status')->default('draft');
            $table->timestampTz('published_at')->nullable(); $table->string('seo_title')->nullable(); $table->text('seo_description')->nullable(); $table->timestamps(); $table->softDeletes(); $table->unique(['brand_id','slug']);
        });
        Schema::create('brand_links', function (Blueprint $table) {
            $table->id(); $table->foreignId('brand_id')->constrained()->cascadeOnDelete(); $table->string('type'); $table->string('label'); $table->text('url');
            $table->integer('sort_order')->default(0); $table->boolean('is_enabled')->default(true); $table->timestamps();
        });
        Schema::create('media', function (Blueprint $table) {
            $table->id(); $table->foreignId('brand_id')->constrained()->cascadeOnDelete(); $table->string('mediable_type'); $table->unsignedBigInteger('mediable_id');
            $table->string('collection'); $table->string('disk')->default('public'); $table->string('path'); $table->string('original_filename'); $table->string('mime_type', 100);
            $table->unsignedBigInteger('file_size'); $table->unsignedInteger('width')->nullable(); $table->unsignedInteger('height')->nullable(); $table->string('alt_text')->nullable();
            $table->string('title')->nullable(); $table->integer('sort_order')->default(0); $table->boolean('is_primary')->default(false); $table->jsonb('variants')->nullable(); $table->jsonb('metadata')->nullable();
            $table->timestamps(); $table->softDeletes(); $table->index(['mediable_type','mediable_id','collection']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('media'); Schema::dropIfExists('brand_links'); Schema::dropIfExists('courses'); Schema::dropIfExists('services'); Schema::dropIfExists('pages'); Schema::dropIfExists('brands');
    }
};
