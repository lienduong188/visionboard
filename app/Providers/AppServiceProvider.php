<?php

namespace App\Providers;

use App\Models\Goal;
use App\Policies\GoalPolicy;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
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
        // Force all Carbon instances to use Asia/Tokyo timezone
        Carbon::setLocale('ja');
        date_default_timezone_set('Asia/Tokyo');

        Vite::prefetch(concurrency: 3);

        // Force HTTPS when APP_URL uses https
        if (str_starts_with(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Register policies
        Gate::policy(Goal::class, GoalPolicy::class);
    }
}
