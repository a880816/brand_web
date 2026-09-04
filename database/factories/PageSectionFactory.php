<?php

namespace Database\Factories;

use App\Models\{Brand, Page, PageSection};
use Illuminate\Database\Eloquent\Factories\Factory;

class PageSectionFactory extends Factory
{
    protected $model = PageSection::class;
    public function definition(): array
    {
        return ['brand_id'=>Brand::factory(),'page_id'=>Page::factory(),'type'=>'text','variant'=>'default','heading'=>fake()->sentence(4),'body'=>fake()->paragraph(),'settings'=>[],'sort_order'=>0,'status'=>'active'];
    }
}
