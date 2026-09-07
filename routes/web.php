<?php

use App\Http\Controllers\Admin\{BrandController,BrandSettingsController,BrandSwitchController,CourseController,CourseSessionController,DashboardController,HomepageController,MaterialController,MediaLibraryController,PlantSpecimenController,PlantVarietyController,ProfileController,UserController};
use App\Http\Controllers\Admin\CourseRegistrationController as AdminCourseRegistrationController;
use App\Http\Controllers\Admin\SaleOrderController;
use App\Http\Controllers\{AuthController,CourseNoticeController,CourseNoticeQrController,CourseRegistrationController,OrderRecipientController,SiteController};
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
        Route::get('/media',[MediaLibraryController::class,'index'])->name('media.index');
        Route::put('/media/{id}',[MediaLibraryController::class,'update'])->name('media.update');
        Route::delete('/media/{id}',[MediaLibraryController::class,'destroy'])->name('media.destroy');
        Route::get('/homepage',[HomepageController::class,'edit'])->name('homepage.edit');
        Route::put('/homepage',[HomepageController::class,'update'])->name('homepage.update');
        Route::post('/homepage/publish',[HomepageController::class,'publish'])->name('homepage.publish');
        Route::get('/homepage/preview',[HomepageController::class,'preview'])->name('homepage.preview');
        Route::get('/courses',[CourseController::class,'index'])->name('courses.index');
        Route::get('/courses/create',[CourseController::class,'create'])->name('courses.create');
        Route::post('/courses',[CourseController::class,'store'])->name('courses.store');
        Route::get('/courses/{course}/edit',[CourseController::class,'edit'])->name('courses.edit');
        Route::put('/courses/{course}',[CourseController::class,'update'])->name('courses.update');
        Route::post('/courses/{course}/publish',[CourseController::class,'publish'])->name('courses.publish');
        Route::post('/courses/{course}/unpublish',[CourseController::class,'unpublish'])->name('courses.unpublish');
        Route::delete('/courses/{course}',[CourseController::class,'destroy'])->name('courses.destroy');
        Route::get('/courses/{course}/sessions/create',[CourseSessionController::class,'create'])->name('course-sessions.create');
        Route::post('/courses/{course}/sessions',[CourseSessionController::class,'store'])->name('course-sessions.store');
        Route::get('/courses/{course}/sessions/{session}/edit',[CourseSessionController::class,'edit'])->name('course-sessions.edit');
        Route::put('/courses/{course}/sessions/{session}',[CourseSessionController::class,'update'])->name('course-sessions.update');
        Route::get('/registrations',[AdminCourseRegistrationController::class,'index'])->name('registrations.index');
        Route::get('/registrations/{registration}/edit',[AdminCourseRegistrationController::class,'edit'])->name('registrations.edit');
        Route::put('/registrations/{registration}',[AdminCourseRegistrationController::class,'update'])->name('registrations.update');
        Route::resource('plant-varieties',PlantVarietyController::class)->parameters(['plant-varieties'=>'plantVariety'])->except('show');
        Route::get('/plant-varieties/{plantVariety}/specimens/create',[PlantSpecimenController::class,'create'])->name('plant-specimens.create');
        Route::post('/plant-varieties/{plantVariety}/specimens',[PlantSpecimenController::class,'store'])->name('plant-specimens.store');
        Route::get('/plant-varieties/{plantVariety}/specimens/{plantSpecimen}/edit',[PlantSpecimenController::class,'edit'])->name('plant-specimens.edit');
        Route::put('/plant-varieties/{plantVariety}/specimens/{plantSpecimen}',[PlantSpecimenController::class,'update'])->name('plant-specimens.update');
        Route::resource('materials',MaterialController::class)->except(['show','destroy']);
        Route::get('/orders',[SaleOrderController::class,'index'])->name('orders.index');
        Route::get('/orders/create',[SaleOrderController::class,'create'])->name('orders.create');
        Route::post('/orders',[SaleOrderController::class,'store'])->name('orders.store');
        Route::get('/orders/{order}/edit',[SaleOrderController::class,'edit'])->name('orders.edit');
        Route::put('/orders/{order}',[SaleOrderController::class,'update'])->name('orders.update');
        Route::post('/orders/{order}/recipient-link',[SaleOrderController::class,'regenerate'])->name('orders.recipient-link');
        Route::post('/orders/{order}/paid',[SaleOrderController::class,'paid'])->name('orders.paid');
        Route::post('/orders/{order}/void',[SaleOrderController::class,'void'])->name('orders.void');
        Route::delete('/orders/{order}',[SaleOrderController::class,'destroy'])->name('orders.destroy');
        Route::resource('brands',BrandController::class)->except('show');
        Route::resource('users',UserController::class)->only(['index','create','store','edit','update']);
    });

    Route::get('/',[SiteController::class,'home'])->name('home');
    Route::get('/courses',[SiteController::class,'courses'])->name('courses.index');
    Route::get('/courses/{slug}',[SiteController::class,'course'])->name('courses.show');
    Route::get('/courses/{slug}/sessions/{session}/register',[CourseRegistrationController::class,'create'])->name('registrations.create');
    Route::post('/courses/{slug}/sessions/{session}/register',[CourseRegistrationController::class,'store'])->middleware('throttle:10,1')->name('registrations.store');
    Route::get('/registrations/{reference}',[CourseRegistrationController::class,'show'])->name('registrations.show');
    Route::get('/course-notice/{slug}',CourseNoticeController::class)->name('course-notice.show');
    Route::get('/course-notice/{slug}/qr.svg',CourseNoticeQrController::class)->name('course-notice.qr');
    Route::get('/order-recipient/{reference}/{token}',[OrderRecipientController::class,'edit'])->name('order-recipient.edit');
    Route::put('/order-recipient/{reference}/{token}',[OrderRecipientController::class,'update'])->middleware('throttle:10,1')->name('order-recipient.update');
    Route::get('/shop',[SiteController::class,'shop'])->name('shop.index');
    Route::get('/shop/plants/{slug}',[SiteController::class,'plant'])->name('shop.plants.show');
    Route::get('/shop/plants/{slug}/specimens/{specimen}',[SiteController::class,'specimen'])->name('shop.specimens.show');
    Route::get('/shop/materials/{slug}',[SiteController::class,'material'])->name('shop.materials.show');
    Route::get('/shop/sold/{soldUnit}',[SiteController::class,'soldUnit'])->name('shop.sold.show');
    Route::fallback(fn()=>abort(404));
});
