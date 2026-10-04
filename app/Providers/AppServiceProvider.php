<?php

namespace App\Providers;

use App\Events\ListingBecameLive;
use App\Listeners\GenerateSavedSearchAlerts;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Event;
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
        // Inertia props are consumed directly by Vue, so the extra "data"
        // envelope around every resource just gets in the way. Paginated
        // collections keep their data/links/meta structure regardless.
        JsonResource::withoutWrapping();

        Event::listen(ListingBecameLive::class, GenerateSavedSearchAlerts::class);
    }
}
