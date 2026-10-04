<?php

namespace App\Events;

use App\Models\Listing;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ListingBecameLive
{
    use Dispatchable, SerializesModels;

    public function __construct(public Listing $listing) {}
}
