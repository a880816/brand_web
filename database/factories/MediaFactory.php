<?php

namespace Database\Factories;

use App\Models\{Brand, HomepageContent};
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(), 'mediable_type' => HomepageContent::class, 'mediable_id' => 1,
            'collection' => 'gallery', 'disk' => 'public', 'path' => 'demo/placeholder.webp',
            'original_filename' => 'placeholder.webp', 'mime_type' => 'image/webp', 'file_size' => 100,
            'width' => 1200, 'height' => 800, 'alt_text' => '植物空間示意圖', 'sort_order' => 0, 'is_primary' => false,
        ];
    }
}
