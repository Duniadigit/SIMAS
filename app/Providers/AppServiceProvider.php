<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Lazy loading dilarang saat development agar N+1 query langsung ketahuan.
        Model::preventLazyLoading(! $this->app->isProduction());
    }
}
