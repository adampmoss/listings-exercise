<?php

namespace Tests\Feature;

use App\Enums\PropertyType;
use App\Models\SavedSearch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class SavedSearchTest extends TestCase
{
    use RefreshDatabase;

    private function demoUser(): User
    {
        return User::query()->orderBy('id')->first();
    }

    public function test_index_lists_the_authenticated_users_saved_searches(): void
    {
        $this->seed();

        SavedSearch::factory(3)->for($this->demoUser())->create();
        SavedSearch::factory(2)->create();

        $this->get('/saved-searches')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('SavedSearches/Index')
                ->has('savedSearches', 3)
            );
    }

    public function test_index_provides_region_and_property_type_options(): void
    {
        $this->seed();

        $this->get('/saved-searches')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->has('regions')
                ->has('propertyTypes', count(PropertyType::cases()), fn (AssertableInertia $type) => $type
                    ->hasAll('value', 'label')
                )
            );
    }

    public function test_store_creates_a_saved_search_for_the_authenticated_user(): void
    {
        $this->seed();

        $this->post('/saved-searches', [
            'max_price' => 350000,
            'min_bedrooms' => 2,
            'property_type' => 'flat',
            'region' => 'Manchester',
        ])->assertRedirect(route('saved-searches.index'));

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $this->demoUser()->id,
            'max_price' => 350000,
            'min_bedrooms' => 2,
            'property_type' => 'flat',
            'region' => 'Manchester',
        ]);
    }

    public function test_store_accepts_partial_criteria(): void
    {
        $this->seed();

        $this->post('/saved-searches', [
            'region' => 'Leeds',
        ])->assertRedirect(route('saved-searches.index'));

        $this->assertDatabaseHas('saved_searches', [
            'user_id' => $this->demoUser()->id,
            'max_price' => null,
            'min_bedrooms' => null,
            'property_type' => null,
            'region' => 'Leeds',
        ]);
    }

    public function test_store_rejects_when_no_criteria_are_provided(): void
    {
        $this->seed();

        $this->post('/saved-searches', [])
            ->assertSessionHasErrors('criteria');

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_store_rejects_all_blank_criteria(): void
    {
        $this->seed();

        $this->post('/saved-searches', [
            'max_price' => '',
            'min_bedrooms' => '',
            'property_type' => '',
            'region' => '',
        ])->assertSessionHasErrors('criteria');

        $this->assertDatabaseCount('saved_searches', 0);
    }

    public function test_store_validates_property_type(): void
    {
        $this->seed();

        $this->post('/saved-searches', [
            'property_type' => 'castle',
        ])->assertSessionHasErrors('property_type');
    }

    public function test_store_validates_max_price_is_non_negative(): void
    {
        $this->seed();

        $this->post('/saved-searches', [
            'max_price' => -1,
        ])->assertSessionHasErrors('max_price');
    }

    public function test_store_validates_min_bedrooms_range(): void
    {
        $this->seed();

        $this->post('/saved-searches', [
            'min_bedrooms' => 21,
        ])->assertSessionHasErrors('min_bedrooms');
    }

    public function test_destroy_deletes_the_users_saved_search(): void
    {
        $this->seed();

        $search = SavedSearch::factory()->for($this->demoUser())->create();

        $this->delete("/saved-searches/{$search->id}")
            ->assertRedirect(route('saved-searches.index'));

        $this->assertDatabaseMissing('saved_searches', ['id' => $search->id]);
    }

    public function test_destroy_rejects_another_users_saved_search(): void
    {
        $this->seed();

        $otherUser = User::factory()->create();
        $search = SavedSearch::factory()->for($otherUser)->create();

        $this->delete("/saved-searches/{$search->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('saved_searches', ['id' => $search->id]);
    }
}
