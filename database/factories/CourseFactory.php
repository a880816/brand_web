<?php
namespace Database\Factories; use App\Models\Brand; use Illuminate\Database\Eloquent\Factories\Factory;
class CourseFactory extends Factory { public function definition(): array{return ['brand_id'=>Brand::factory(),'name'=>fake()->sentence(3),'slug'=>fake()->unique()->slug(),'summary'=>fake()->sentence(),'description'=>fake()->paragraphs(3,true),'location_note'=>'品牌空間','duration_minutes'=>120,'price_amount'=>1800,'price_note'=>'材料費另計','sort_order'=>0,'status'=>'published','published_at'=>now()];} }
