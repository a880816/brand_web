<?php

namespace Database\Seeders;

use App\Models\{Brand, Course, HomepageContent, Material, Media, PlantSpecimen, PlantVariety};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->removeSeededMedia();

        $brands = [
            [
                'name' => '蕨光植研', 'slug' => 'verdant', 'domain' => 'brand-a.localhost',
                'primary_color' => '#315c45', 'secondary_color' => '#91aa96', 'accent_color' => '#d4a85f',
                'background_color' => '#f4f1e8', 'text_color' => '#17231c',
                'facebook_url' => 'https://www.facebook.com/', 'instagram_url' => 'https://www.instagram.com/',
                'theme_settings' => ['eyebrow' => 'VERDANT BOTANICAL STUDIO'],
                'seo_title' => '蕨光植研｜城市裡的植物生活提案',
                'seo_description' => '以植物、課程與手作資材，陪你打造長久生長的綠意生活。',
            ],
            [
                'name' => '土日植所', 'slug' => 'terracotta', 'domain' => 'brand-b.localhost',
                'primary_color' => '#8b4d35', 'secondary_color' => '#c8956e', 'accent_color' => '#5d7760',
                'background_color' => '#f7eee5', 'text_color' => '#30221c',
                'facebook_url' => 'https://www.facebook.com/', 'instagram_url' => 'https://www.instagram.com/',
                'theme_settings' => ['eyebrow' => 'TERRA & LEAF'],
                'seo_title' => '土日植所｜植物與手作器物',
                'seo_description' => '從植物、土壤到手作器皿，建立有溫度的居家綠景。',
            ],
        ];

        foreach ($brands as $index => $data) {
            $brand = Brand::updateOrCreate(['slug' => $data['slug']], $data + [
                'status' => 'active', 'home_menu_label' => '首頁', 'courses_menu_label' => '手作課程',
                'shop_menu_label' => '線上商店', 'bank_name' => '本機展示銀行', 'bank_code' => '000',
                'bank_branch' => '展示分行', 'bank_account_name' => $data['name'],
                'bank_account_number' => '000000000000', 'remittance_notice' => '此為本機展示帳戶，請勿實際匯款。',
            ]);

            $courseSlug = $index ? 'seasonal-planting' : 'foliage-basics';
            $course = Course::updateOrCreate(['brand_id' => $brand->id, 'slug' => $courseSlug], [
                'name' => $index ? '季節植栽創作' : '觀葉植物照顧入門',
                'summary' => '從植物特性與日常照顧開始，完成一份可以帶回家的作品。',
                'description' => '課程包含示範、實作與照顧說明，適合沒有經驗的參與者。',
                'suitable_for' => '初次接觸植物與想建立照顧基礎的人。',
                'precautions' => '請穿著方便活動的服裝並準時抵達。',
                'duration_minutes' => 120, 'price_amount' => 1000, 'sort_order' => 0,
                'status' => 'published', 'published_at' => now(),
            ]);
            $course->plans()->updateOrCreate(['course_session_id' => null, 'name' => '單人方案'], ['brand_id' => $brand->id, 'participants' => 1, 'price' => 1000, 'sort_order' => 0, 'is_enabled' => true]);
            $course->sessions()->firstOrCreate([], [
                'brand_id' => $brand->id, 'starts_at' => now()->addWeeks(3)->setTime(14, 0),
                'ends_at' => now()->addWeeks(3)->setTime(16, 0), 'city' => '台北市',
                'venue_name' => $brand->name.'工作室', 'address' => '中正區展示路 1 號',
                'google_maps_url' => 'https://maps.google.com/?q=Taipei', 'capacity' => 8,
                'registration_close_days' => 3, 'status' => 'open',
            ]);

            $variety = PlantVariety::updateOrCreate(['brand_id' => $brand->id, 'slug' => 'demo-platycerium'], [
                'name' => '鹿角蕨示範品種', 'scientific_name' => 'Platycerium demo',
                'variety_code' => strtoupper($brand->slug).'-001',
                'description' => '展示用的品種與母本說明。', 'care_instructions' => '明亮散射光，介質乾燥後充分澆水。',
                'status' => 'published', 'published_at' => now(), 'sort_order' => 0,
            ]);
            $specimen = PlantSpecimen::where('plant_variety_id', $variety->id)->where('sequence', 1)->first();
            if (! $specimen) $specimen = PlantSpecimen::forceCreate([
                'brand_id'=>$brand->id, 'plant_variety_id'=>$variety->id, 'sequence'=>1, 'custom_name'=>'A株',
                'full_tag_name'=>$variety->scientific_name.' '.$variety->variety_code.' A株', 'description'=>'葉型完整的展示實株。',
                'specifications'=>[['name'=>'板徑','value'=>'15cm']], 'price'=>1200, 'stock_on_hand'=>1,
                'reserved_quantity'=>0, 'sold_sequence'=>0, 'status'=>'published', 'published_at'=>now(),
            ]);
            $material = Material::updateOrCreate(['brand_id' => $brand->id, 'slug' => 'planting-medium'], [
                'product_code' => strtoupper($brand->slug).'-MAT-001', 'name' => '植栽介質包', 'description' => '適合觀葉植物的基礎介質。',
                'specifications' => [['name' => '容量', 'value' => '2L']], 'price' => 180,
                'stock_on_hand' => 10, 'reserved_quantity' => 0, 'low_stock_threshold' => 3,
                'status' => 'published', 'published_at' => now(),
            ]);

            $homepage = HomepageContent::firstOrCreate(['brand_id' => $brand->id], ['draft_data' => [], 'published_data' => [], 'published_at' => now()]);
            $homepageData = [
                'hero_title' => $index ? '讓植物與器物，慢慢成為日常' : '把一方綠意，安放進生活',
                'hero_subtitle' => '手作課程、植株與資材，由品牌後台獨立維護。',
                'hero_primary_label' => '探索課程', 'hero_secondary_label' => '逛逛商店',
                'intro_title' => '與植物一起生活', 'intro_body' => '從一株植物開始，找到適合自己的照顧節奏。',
                'featured_course_ids' => [$course->id], 'featured_product_keys' => ['plant:'.$variety->id, 'material:'.$material->id],
                'gallery_items' => [],
            ];
            $homepage->update(['draft_data' => $homepageData, 'published_data' => $homepageData]);

        }

        $this->call(AdminUserSeeder::class);
    }

    private function removeSeededMedia(): void
    {
        Media::where('original_filename', 'botanical-studio.png')->whereNull('variants')->get()->each(function (Media $media) {
            Storage::disk($media->disk)->delete(array_merge([$media->path], array_values($media->variants ?? [])));
            $media->forceDelete();
        });
    }
}
