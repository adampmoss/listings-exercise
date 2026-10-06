<?php

namespace App\Observers;

use App\Enums\ListingStatus;
use App\Events\ListingBecameLive;
use App\Models\Listing;

class ListingObserver
{
    public function created(Listing $listing): void
    {
        if ($listing->status === ListingStatus::Live) {
            ListingBecameLive::dispatch($listing);
        }
    }

    public function updated(Listing $listing): void
    {
        if (
            $listing->wasChanged('status')
            && $listing->status === ListingStatus::Live
            && $listing->getOriginal('status') !== ListingStatus::Live->value
        ) {
            ListingBecameLive::dispatch($listing);
        }
    }
}
