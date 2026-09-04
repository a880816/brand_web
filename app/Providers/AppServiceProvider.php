<?php

namespace App\Providers;

use App\Http\Middleware\{EnsureAccountActive, ResolveAdminBrand, ResolveBrand};
use App\Support\BrandContext;
use App\Models\{Brand,BrandLink,Course,Media,Page,PageSection,Service,User};
use App\Policies\{BrandPolicy,TenantContentPolicy,UserPolicy};
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(BrandContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach ([Page::class,PageSection::class,Service::class,Course::class,Media::class,BrandLink::class] as $model) Gate::policy($model,TenantContentPolicy::class);
        Gate::policy(Brand::class,BrandPolicy::class);
        Gate::policy(User::class,UserPolicy::class);
        Livewire::addPersistentMiddleware([
            ResolveBrand::class,
            EnsureAccountActive::class,
            ResolveAdminBrand::class,
        ]);
    }
}
