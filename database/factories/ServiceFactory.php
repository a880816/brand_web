<?php
namespace Database\Factories; use App\Models\Brand; use Illuminate\Database\Eloquent\Factories\Factory;
class ServiceFactory extends Factory { public function definition(): array{return ['brand_id'=>Brand::factory(),'name'=>fake()->sentence(3),'slug'=>fake()->unique()->slug(),'summary'=>fake()->sentence(),'description'=>fake()->paragraphs(3,true),'sort_order'=>0,'status'=>'published','published_at'=>now()];} }
