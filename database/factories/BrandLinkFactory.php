<?php
namespace Database\Factories; use App\Models\Brand; use Illuminate\Database\Eloquent\Factories\Factory;
class BrandLinkFactory extends Factory { public function definition(): array{return ['brand_id'=>Brand::factory(),'type'=>'instagram','label'=>'Instagram','url'=>'https://www.instagram.com/','sort_order'=>0,'is_enabled'=>true];} }
