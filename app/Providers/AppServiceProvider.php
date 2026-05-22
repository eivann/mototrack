<?php

namespace App\Providers;

use App\Models\InventoryItem;
use App\Models\PreOrder;
use App\Models\Repair;
use App\Models\Sale;
use App\Policies\InventoryPolicy;
use App\Policies\PreOrderPolicy;
use App\Policies\RepairPolicy;
use App\Policies\SalePolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(InventoryItem::class, InventoryPolicy::class);
        Gate::policy(Repair::class, RepairPolicy::class);
        Gate::policy(PreOrder::class, PreOrderPolicy::class);
        Gate::policy(Sale::class, SalePolicy::class);

        \Illuminate\Support\Facades\Schema::defaultStringLength(191);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
        });
    }
}
