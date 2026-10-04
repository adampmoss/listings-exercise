<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\Branch;
use App\Models\Listing;
use App\Models\SavedSearch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedSearchMatchTest extends TestCase
{
    use RefreshDatabase;

    private function makeListing(array $attributes = []): Listing
    {
        $branch = Branch::factory()->create(['region' => $attributes['region'] ?? 'Manchester']);
        unset($attributes['region']);

        return Listing::factory()->live()->for($branch)->create($attributes);
    }

    public function test_matches_when_all_criteria_are_satisfied(): void
    {
        $listing = $this->makeListing([
            'price' => 250_000,
            'bedrooms' => 3,
            'property_type' => PropertyType::Flat,
            'region' => 'Manchester',
        ]);

        $search = SavedSearch::factory()->create([
            'max_price' => 300_000,
            'min_bedrooms' => 2,
            'property_type' => PropertyType::Flat,
            'region' => 'Manchester',
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_does_not_match_when_price_exceeds_max(): void
    {
        $listing = $this->makeListing(['price' => 400_000]);

        $search = SavedSearch::factory()->create([
            'max_price' => 300_000,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertFalse($search->matches($listing));
    }

    public function test_matches_when_price_equals_max(): void
    {
        $listing = $this->makeListing(['price' => 300_000]);

        $search = SavedSearch::factory()->create([
            'max_price' => 300_000,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_does_not_match_when_bedrooms_below_minimum(): void
    {
        $listing = $this->makeListing(['bedrooms' => 1]);

        $search = SavedSearch::factory()->create([
            'min_bedrooms' => 2,
            'max_price' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertFalse($search->matches($listing));
    }

    public function test_matches_when_bedrooms_equal_minimum(): void
    {
        $listing = $this->makeListing(['bedrooms' => 2]);

        $search = SavedSearch::factory()->create([
            'min_bedrooms' => 2,
            'max_price' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_does_not_match_when_property_type_differs(): void
    {
        $listing = $this->makeListing(['property_type' => PropertyType::Detached]);

        $search = SavedSearch::factory()->create([
            'property_type' => PropertyType::Flat,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->assertFalse($search->matches($listing));
    }

    public function test_does_not_match_when_region_differs(): void
    {
        $listing = $this->makeListing(['region' => 'Leeds']);

        $search = SavedSearch::factory()->create([
            'region' => 'Manchester',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        $this->assertFalse($search->matches($listing));
    }

    public function test_null_max_price_matches_any_price(): void
    {
        $listing = $this->makeListing(['price' => 9_999_999]);

        $search = SavedSearch::factory()->create([
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_null_min_bedrooms_matches_any_bedroom_count(): void
    {
        $listing = $this->makeListing(['bedrooms' => 1]);

        $search = SavedSearch::factory()->create([
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_null_property_type_matches_any_type(): void
    {
        $listing = $this->makeListing(['property_type' => PropertyType::Bungalow]);

        $search = SavedSearch::factory()->create([
            'property_type' => null,
            'max_price' => null,
            'min_bedrooms' => null,
            'region' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_null_region_matches_any_region(): void
    {
        $listing = $this->makeListing(['region' => 'Newcastle']);

        $search = SavedSearch::factory()->create([
            'region' => null,
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_all_null_criteria_matches_any_listing(): void
    {
        $listing = $this->makeListing();

        $search = SavedSearch::factory()->create([
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_partial_criteria_only_restricts_specified_fields(): void
    {
        $listing = $this->makeListing([
            'price' => 500_000,
            'bedrooms' => 1,
            'property_type' => PropertyType::Terraced,
            'region' => 'Bristol',
        ]);

        $search = SavedSearch::factory()->create([
            'region' => 'Bristol',
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
        ]);

        $this->assertTrue($search->matches($listing));
    }

    public function test_fails_on_first_unmet_criterion_among_mixed_criteria(): void
    {
        $listing = $this->makeListing([
            'price' => 200_000,
            'bedrooms' => 4,
            'property_type' => PropertyType::Detached,
            'region' => 'Leeds',
        ]);

        $search = SavedSearch::factory()->create([
            'max_price' => 250_000,
            'min_bedrooms' => 3,
            'property_type' => PropertyType::Detached,
            'region' => 'Manchester',
        ]);

        $this->assertFalse($search->matches($listing));
    }
}
