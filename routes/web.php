<?php

use App\Http\Controllers\Admin\{BrandController,BrandLinkController,BrandSettingsController,BrandSwitchController,ContentController,DashboardController,HomepageController,MediaLibraryController,PreviewController,ProfileController,UserController};
use App\Http\Controllers\{AuthController,SiteController};
use Illuminate\Support\Facades\Route;

Route::middleware('brand')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login',[AuthController::class,'create'])->name('login');
        Route::post('/login',[AuthController::class,'store'])->name('login.store');
        Route::get('/forgot-password',[AuthController::class,'forgot'])->name('password.request');
        Route::post('/forgot-password',[AuthController::class,'emailReset'])->name('password.email');
        Route::get('/reset-password/{token}',[AuthController::class,'reset'])->name('password.reset');
        Route::post('/reset-password',[AuthController::class,'updatePassword'])->name('password.update');
    });
    Route::post('/logout',[AuthController::class,'destroy'])->middleware('auth')->name('logout');

    Route::prefix('admin')->name('admin.')->middleware(['auth','active','admin.brand'])->group(function () {
        Route::get('/',DashboardController::class)->name('dashboard');
        Route::get('/profile',[ProfileController::class,'edit'])->name('profile');
        Route::put('/profile',[ProfileController::class,'update'])->name('profile.update');
        Route::put('/profile/password',[ProfileController::class,'password'])->name('profile.password');
        Route::post('/switch-brand/{brand}',BrandSwitchController::class)->name('switch-brand');
        Route::get('/settings',[BrandSettingsController::class,'edit'])->name('settings');
        Route::put('/settings',[BrandSettingsController::class,'update'])->name('settings.update');
        Route::get('/links',[BrandLinkController::class,'index'])->name('links.index');
        Route::post('/links',[BrandLinkController::class,'store'])->name('links.store');
        Route::put('/links/{link}',[BrandLinkController::class,'update'])->name('links.update');
        Route::delete('/links/{link}',[BrandLinkController::class,'destroy'])->name('links.destroy');
        Route::get('/media',[MediaLibraryController::class,'index'])->name('media.index');
        Route::put('/media/{id}',[MediaLibraryController::class,'update'])->name('media.update');
        Route::delete('/media/{id}',[MediaLibraryController::class,'destroy'])->name('media.destroy');
        Route::get('/homepage',[HomepageController::class,'edit'])->name('homepage.edit');
        Route::put('/homepage',[HomepageController::class,'update'])->name('homepage.update');
        Route::post('/homepage/publish',[HomepageController::class,'publish'])->name('homepage.publish');
        Route::get('/homepage/preview',[HomepageController::class,'preview'])->name('homepage.preview');
        Route::resource('brands',BrandController::class)->except('show');
        Route::resource('users',UserController::class)->only(['index','create','store','edit','update']);
        Route::get('/{resource}',[ContentController::class,'index'])->name('content.index')->where('resource','pages|services|courses');
        Route::get('/{resource}/create',[ContentController::class,'create'])->name('content.create')->where('resource','pages|services|courses');
        Route::post('/{resource}',[ContentController::class,'store'])->name('content.store')->where('resource','pages|services|courses');
        Route::get('/{resource}/{id}/edit',[ContentController::class,'edit'])->name('content.edit')->where('resource','pages|services|courses');
        Route::put('/{resource}/{id}',[ContentController::class,'update'])->name('content.update')->where('resource','pages|services|courses');
        Route::post('/{resource}/{id}/publish',[ContentController::class,'publish'])->name('content.publish')->where('resource','pages|services|courses');
        Route::post('/{resource}/{id}/unpublish',[ContentController::class,'unpublish'])->name('content.unpublish')->where('resource','pages|services|courses');
        Route::get('/{resource}/{id}/preview',PreviewController::class)->name('content.preview')->where('resource','pages|services|courses');
        Route::delete('/{resource}/{id}',[ContentController::class,'destroy'])->name('content.destroy')->where('resource','pages|services|courses');
    });

    Route::get('/',[SiteController::class,'home'])->name('home');
    Route::get('/about',[SiteController::class,'about'])->name('about');
    Route::get('/services',[SiteController::class,'services'])->name('services.index');
    Route::get('/services/{slug}',[SiteController::class,'service'])->name('services.show');
    Route::get('/courses',[SiteController::class,'courses'])->name('courses.index');
    Route::get('/courses/{slug}',[SiteController::class,'course'])->name('courses.show');
    Route::get('/shop',[SiteController::class,'shop'])->name('shop.index');
    Route::fallback(fn()=>abort(404));
});
