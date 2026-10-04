<?php

namespace App\Listeners;

use App\Events\ListingBecameLive;
use App\Models\Alert;
use App\Models\SavedSearch;
use Illuminate\Contracts\Queue\ShouldQueue;

class GenerateSavedSearchAlerts implements ShouldQueue
{
    public function handle(ListingBecameLive $event): void
    {
        $listing = $event->listing->loadMissing('branch');

        SavedSearch::query()
            ->cursor()
            ->filter(fn (SavedSearch $search) => $search->matches($listing))
            ->each(function (SavedSearch $search) use ($listing) {
                Alert::query()->firstOrCreate(
                    [
                        'user_id' => $search->user_id,
                        'listing_id' => $listing->id,
                    ],
                    [
                        'saved_search_id' => $search->id,
                    ],
                );
            });
    }
}
