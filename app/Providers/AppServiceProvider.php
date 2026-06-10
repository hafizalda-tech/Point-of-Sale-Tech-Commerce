<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        view()->composer('layouts.master', function ($view) {
            $view->with('setting', Setting::first());
        });

        view()->composer('layouts.auth', function ($view) {
            $view->with('setting', Setting::first());
        });

        view()->composer('auth.login', function ($view) {
            $view->with('setting', Setting::first());
        });
    }

    public function boot()
    {
        URL::forceScheme('https');
    }
}
