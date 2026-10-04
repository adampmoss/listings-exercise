<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\Alert;
use App\Models\Branch;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AlertPageTest extends TestCase
{
    use RefreshDatabase;

    private function demoUser(): User
    {
        return User::query()->orderBy('id')->first();
    }

    public function test_index_renders_the_authenticated_users_alerts(): void
    {
        $this->seed();
        $user = $this->demoUser();

        $search = SavedSearch::factory()->for($user)->create();

        Alert::factory(3)->for($user)->for($search)->create();

        // Another user's alert — must not appear.
        Alert::factory()->create();

        $this->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Alerts/Index')
                ->has('alerts.data', 3)
            );
    }

    public function test_index_does_not_show_another_users_alerts(): void
    {
        $this->seed();

        $otherUser = User::factory()->create();
        Alert::factory(2)->for($otherUser)->create();

        $this->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('alerts.data', 0)
            );
    }

    public function test_index_includes_listing_data_for_the_ui(): void
    {
        $this->seed();
        $user = $this->demoUser();

        $branch = Branch::factory()->create(['region' => 'Manchester']);
        $search = SavedSearch::factory()->for($user)->create([
            'region' => 'Manchester',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        $listing = Listing::factory()->for($branch)->create([
            'price' => 250_000,
            'bedrooms' => 3,
            'property_type' => PropertyType::Flat,
        ]);

        Alert::factory()->for($user)->create([
            'saved_search_id' => $search->id,
            'listing_id' => $listing->id,
        ]);

        $this->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('alerts.data.0', fn (AssertableInertia $alert) => $alert
                    ->has('id')
                    ->has('created_at')
                    ->has('read_at')
                    ->has('listing', fn (AssertableInertia $l) => $l
                        ->where('id', $listing->id)
                        ->where('price', 250_000)
                        ->where('bedrooms', 3)
                        ->where('property_type', 'flat')
                        ->has('address_line_1')
                        ->has('branch.region')
                        ->etc()
                    )
                    ->has('saved_search', fn (AssertableInertia $s) => $s
                        ->where('region', 'Manchester')
                        ->etc()
                    )
                )
            );
    }

    public function test_index_is_paginated(): void
    {
        $this->seed();
        $user = $this->demoUser();

        $search = SavedSearch::factory()->for($user)->create();

        Alert::factory(20)->for($user)->for($search)->create();

        $this->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('alerts.data', 15)
                ->has('alerts.links')
            );
    }

    public function test_index_orders_alerts_newest_first(): void
    {
        $this->seed();
        $user = $this->demoUser();

        $search = SavedSearch::factory()->for($user)->create();

        $oldListing = Listing::factory()->create();
        $newListing = Listing::factory()->create();

        Alert::factory()->for($user)->create([
            'saved_search_id' => $search->id,
            'listing_id' => $oldListing->id,
            'created_at' => now()->subDay(),
        ]);

        Alert::factory()->for($user)->create([
            'saved_search_id' => $search->id,
            'listing_id' => $newListing->id,
            'created_at' => now(),
        ]);

        $this->get('/alerts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->where('alerts.data.0.listing.id', $newListing->id)
                ->where('alerts.data.1.listing.id', $oldListing->id)
            );
    }
}
