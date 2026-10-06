<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Enums\PropertyType;
use App\Models\Branch;
use App\Models\Listing;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AlertGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_alert_is_generated_when_a_matching_listing_becomes_live(): void
    {
        $branch = Branch::factory()->create(['region' => 'Manchester']);
        $user = User::factory()->create();

        $search = SavedSearch::factory()->for($user)->create([
            'max_price' => 300_000,
            'min_bedrooms' => 2,
            'property_type' => PropertyType::Flat,
            'region' => 'Manchester',
        ]);

        $listing = Listing::factory()->for($branch)->create([
            'price' => 250_000,
            'bedrooms' => 3,
            'property_type' => PropertyType::Flat,
            'status' => ListingStatus::Draft,
        ]);

        $this->assertDatabaseCount('alerts', 0);

        $listing->update([
            'status' => ListingStatus::Live,
            'listed_at' => now(),
        ]);

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseHas('alerts', [
            'user_id' => $user->id,
            'saved_search_id' => $search->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_no_alert_for_a_non_matching_saved_search(): void
    {
        $branch = Branch::factory()->create(['region' => 'Manchester']);
        $user = User::factory()->create();

        SavedSearch::factory()->for($user)->create([
            'max_price' => 200_000,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $listing = Listing::factory()->for($branch)->create([
            'price' => 400_000,
            'status' => ListingStatus::Draft,
        ]);

        $listing->update([
            'status' => ListingStatus::Live,
            'listed_at' => now(),
        ]);

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_no_alert_when_a_listing_is_created_as_draft(): void
    {
        $user = User::factory()->create();

        SavedSearch::factory()->for($user)->create([
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        Listing::factory()->draft()->create();

        $this->assertDatabaseCount('alerts', 0);
    }

    public function test_no_alert_when_an_already_live_listing_is_updated(): void
    {
        $branch = Branch::factory()->create(['region' => 'Manchester']);
        $user = User::factory()->create();

        SavedSearch::factory()->for($user)->create([
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $listing = Listing::factory()->for($branch)->create([
            'status' => ListingStatus::Draft,
        ]);

        $listing->update([
            'status' => ListingStatus::Live,
            'listed_at' => now(),
        ]);

        $this->assertDatabaseCount('alerts', 1);

        $listing->update(['price' => $listing->price + 10_000]);

        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_alert_is_generated_when_a_listing_is_created_directly_as_live(): void
    {
        $branch = Branch::factory()->create(['region' => 'Leeds']);
        $user = User::factory()->create();

        $search = SavedSearch::factory()->for($user)->create([
            'region' => 'Leeds',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        $listing = Listing::factory()->live()->for($branch)->create();

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseHas('alerts', [
            'user_id' => $user->id,
            'saved_search_id' => $search->id,
            'listing_id' => $listing->id,
        ]);
    }

    public function test_duplicate_alert_is_not_created_when_multiple_searches_match(): void
    {
        $branch = Branch::factory()->create(['region' => 'Manchester']);
        $user = User::factory()->create();

        SavedSearch::factory()->for($user)->create([
            'region' => 'Manchester',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        SavedSearch::factory()->for($user)->create([
            'max_price' => 500_000,
            'region' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        Listing::factory()->live()->for($branch)->create(['price' => 250_000]);

        $this->assertDatabaseCount('alerts', 1);
    }

    public function test_alerts_are_scoped_to_the_correct_users(): void
    {
        $branch = Branch::factory()->create(['region' => 'Manchester']);

        $alice = User::factory()->create();
        $bob = User::factory()->create();

        SavedSearch::factory()->for($alice)->create([
            'region' => 'Manchester',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        SavedSearch::factory()->for($bob)->create([
            'region' => 'Leeds',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        Listing::factory()->live()->for($branch)->create();

        $this->assertDatabaseCount('alerts', 1);
        $this->assertDatabaseHas('alerts', ['user_id' => $alice->id]);
        $this->assertDatabaseMissing('alerts', ['user_id' => $bob->id]);
    }
}
