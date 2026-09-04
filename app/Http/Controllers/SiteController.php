<?php
namespace App\Http\Controllers;
use App\Support\BrandContext;
class SiteController extends Controller {
 public function __construct(private BrandContext $context){}
 public function home(){ $b=$this->context->brand(); return view('site.home',['page'=>$b->pages()->with(['sections.media'])->published()->where('type','home')->firstOrFail(),'services'=>$b->services()->published()->orderBy('sort_order')->limit(3)->get(),'courses'=>$b->courses()->published()->orderBy('sort_order')->limit(3)->get(),'links'=>$b->links()->get()]); }
 public function about(){return view('site.about',['page'=>$this->context->brand()->pages()->with(['sections.media'])->published()->where('type','about')->firstOrFail()]);}
 public function services(){return view('site.index',['kind'=>'服務','items'=>$this->context->brand()->services()->published()->orderBy('sort_order')->get(),'route'=>'services.show']);}
 public function service(string $slug){return view('site.detail',['kind'=>'服務','item'=>$this->context->brand()->services()->published()->where('slug',$slug)->firstOrFail(),'back'=>'services.index']);}
 public function courses(){return view('site.index',['kind'=>'課程','items'=>$this->context->brand()->courses()->published()->orderBy('sort_order')->get(),'route'=>'courses.show']);}
 public function course(string $slug){return view('site.detail',['kind'=>'課程','item'=>$this->context->brand()->courses()->published()->where('slug',$slug)->firstOrFail(),'back'=>'courses.index','links'=>$this->context->brand()->links()->get()]);}
}
