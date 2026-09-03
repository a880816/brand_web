<?php
namespace Database\Factories; use App\Models\Brand; use Illuminate\Database\Eloquent\Factories\Factory;
class PageFactory extends Factory { public function definition(): array{return ['brand_id'=>Brand::factory(),'type'=>'custom','title'=>fake()->sentence(3),'slug'=>fake()->unique()->slug(),'excerpt'=>fake()->sentence(),'body'=>fake()->paragraphs(3,true),'status'=>'published','published_at'=>now()];} }
