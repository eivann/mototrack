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
    public function register(): void
    {
        // Audit #5: cart persistence abstraction.
        $this->app->bind(
            \App\Contracts\CartRepository::class,
            \App\Repositories\SessionCartRepository::class
        );



        $this->app->singleton(\App\Services\SmsGatewayInterface::class, function ($app) {
            $twilioSid = config('services.twilio.sid');
            $twilioAuthToken = config('services.twilio.auth_token');
            $twilioNumber = config('services.twilio.number');

            $moviderApiKey = config('services.movider.api_key');
            $moviderApiSecret = config('services.movider.api_secret');

            if ($twilioSid && $twilioAuthToken && $twilioNumber) {
                return new \App\Services\Sms\TwilioSmsGateway($twilioSid, $twilioAuthToken, $twilioNumber);
            }

            if ($moviderApiKey && $moviderApiSecret) {
                return new \App\Services\Sms\MoviderSmsGateway($moviderApiKey, $moviderApiSecret);
            }

            return new \App\Services\Sms\MockSmsGateway();
        });
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
