<?php
namespace Database\Factories; use Illuminate\Database\Eloquent\Factories\Factory;
class BrandFactory extends Factory { public function definition(): array{return ['name'=>fake()->company(),'slug'=>fake()->unique()->slug(2),'status'=>'active','primary_color'=>'#315c45','secondary_color'=>'#8da58f','accent_color'=>'#d7a35d','background_color'=>'#f5f2e9','text_color'=>'#1d2922','theme_settings'=>[]];} }
