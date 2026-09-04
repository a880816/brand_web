<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('variant', 40)->default('default');
            $table->string('heading')->nullable();
            $table->text('body')->nullable();
            $table->jsonb('settings')->default('{}');
            $table->integer('sort_order')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['brand_id', 'page_id', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
