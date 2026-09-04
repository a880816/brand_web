<?php

namespace Database\Seeders;

use App\Models\{Brand, Media};
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class PageSectionSeeder extends Seeder
{
    public function run(): void
    {
        Brand::with('pages')->get()->each(function (Brand $brand) {
            $home = $brand->pages->firstWhere('type', 'home');
            $about = $brand->pages->firstWhere('type', 'about');
            if (! $home || ! $about) return;

            if ($home->sections()->doesntExist()) {
            $homeSections = [
                ['type'=>'hero','heading'=>data_get($brand->theme_settings,'hero_title'),'body'=>data_get($brand->theme_settings,'hero_note'),'settings'=>['button_label'=>'探索服務','button_url'=>'/services'],'variant'=>'full_width'],
                ['type'=>'text','heading'=>$home->excerpt,'body'=>$home->body,'variant'=>'centered'],
                ['type'=>'services','heading'=>'讓綠意住得更久','settings'=>['limit'=>3]],
            ];
            if ($brand->slug === 'verdant') {
                $homeSections[] = ['type'=>'text','heading'=>'蕨光限定：照顧，也是一種觀看','body'=>'這個區塊只存在於 A 品牌；B 品牌的頁面結構不會被改動。','variant'=>'split'];
            }
            $homeSections = array_merge($homeSections, [
                ['type'=>'gallery','heading'=>'植物日常','body'=>'左右滑動，看看工作室裡正在生長的風景。','settings'=>['autoplay'=>false],'variant'=>'full_width'],
                ['type'=>'courses','heading'=>'和植物相處的練習','settings'=>['limit'=>3]],
                ['type'=>'cta','heading'=>'一起讓植物成為生活的一部分','body'=>'歡迎從品牌社群與我們聯繫。','settings'=>['button_label'=>'查看 Instagram','button_url'=>'https://www.instagram.com/'],'variant'=>'centered'],
            ]);
            foreach ($homeSections as $order => $data) {
                $section = $home->sections()->create($data + ['brand_id'=>$brand->id,'sort_order'=>$order,'status'=>'active']);
                if ($data['type'] === 'gallery') $this->seedGallery($brand, $section);
            }
            }

            if ($about->sections()->doesntExist()) {
                $about->sections()->create(['brand_id'=>$brand->id,'type'=>'hero','variant'=>'default','heading'=>$about->title,'body'=>$about->excerpt,'sort_order'=>0,'status'=>'active']);
                $about->sections()->create(['brand_id'=>$brand->id,'type'=>'image_text','variant'=>$brand->slug === 'verdant'?'split':'default','heading'=>'從一株植物開始','body'=>$about->body,'sort_order'=>1,'status'=>'active']);
            }
        });
    }

    private function seedGallery(Brand $brand, $section): void
    {
        for ($index = 0; $index < 3; $index++) {
            $path = 'brands/'.$brand->id.'/gallery/section-'.$section->id.'-'.$index.'.png';
            Storage::disk('public')->put($path, file_get_contents(public_path('demo/botanical-studio.png')));
            Media::create(['brand_id'=>$brand->id,'mediable_type'=>$section->getMorphClass(),'mediable_id'=>$section->id,'collection'=>'gallery','disk'=>'public','path'=>$path,'original_filename'=>'botanical-studio.png','mime_type'=>'image/png','file_size'=>filesize(public_path('demo/botanical-studio.png')),'width'=>1536,'height'=>1024,'alt_text'=>$brand->name.' 植物日常 '.($index+1),'sort_order'=>$index,'is_primary'=>$index===0]);
        }
    }
}
