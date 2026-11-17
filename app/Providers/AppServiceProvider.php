<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;
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
        // Catch N+1 queries and typos in attribute names early; never in production.
        Model::shouldBeStrict(! $this->app->isProduction());

        // Inertia props are plain objects; only paginated collections keep the data/links/meta envelope.
        JsonResource::withoutWrapping();
    }
}
