<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};

return new class extends Migration {
    public function up():void
    {
        DB::table('media')->whereIn('mediable_type',['App\\Models\\Page','App\\Models\\PageSection','App\\Models\\Service'])->delete();
        Schema::dropIfExists('page_sections');Schema::dropIfExists('brand_links');Schema::dropIfExists('services');Schema::dropIfExists('pages');
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'brand_admin'");
    }

    public function down():void
    {
        Schema::create('pages',function(Blueprint $table){$table->id();$table->foreignId('brand_id')->constrained()->cascadeOnDelete();$table->string('type');$table->string('title');$table->string('slug');$table->text('excerpt')->nullable();$table->text('body')->nullable();$table->string('status')->default('draft');$table->timestampTz('published_at')->nullable();$table->string('seo_title')->nullable();$table->text('seo_description')->nullable();$table->timestamps();$table->softDeletes();$table->unique(['brand_id','slug']);});
        Schema::create('services',function(Blueprint $table){$table->id();$table->foreignId('brand_id')->constrained()->cascadeOnDelete();$table->string('name');$table->string('slug');$table->text('summary')->nullable();$table->text('description')->nullable();$table->integer('sort_order')->default(0);$table->string('status')->default('draft');$table->timestampTz('published_at')->nullable();$table->string('seo_title')->nullable();$table->text('seo_description')->nullable();$table->timestamps();$table->softDeletes();$table->unique(['brand_id','slug']);});
        Schema::create('brand_links',function(Blueprint $table){$table->id();$table->foreignId('brand_id')->constrained()->cascadeOnDelete();$table->string('type');$table->string('label');$table->text('url');$table->integer('sort_order')->default(0);$table->boolean('is_enabled')->default(true);$table->timestamps();});
        Schema::create('page_sections',function(Blueprint $table){$table->id();$table->foreignId('brand_id')->constrained()->cascadeOnDelete();$table->foreignId('page_id')->constrained()->cascadeOnDelete();$table->string('type',40);$table->string('variant',40)->default('default');$table->string('heading')->nullable();$table->text('body')->nullable();$table->jsonb('settings')->default('{}');$table->integer('sort_order')->default(0);$table->string('status',20)->default('active');$table->timestamps();$table->softDeletes();});
        DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'member'");
    }
};
