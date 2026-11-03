<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Enforce application timezone Asia/Jakarta (WIB UTC+7) across all PHP date/time operations
        date_default_timezone_set(config('app.timezone', 'Asia/Jakarta'));
        \Carbon\Carbon::setLocale('id');

        \Illuminate\Pagination\Paginator::defaultView('vendor.pagination.default');
        \Illuminate\Pagination\Paginator::defaultSimpleView('vendor.pagination.simple-default');

        if (!app()->runningInConsole()) {
            \App\Services\MailConfigService::apply();
        }
    }
}
