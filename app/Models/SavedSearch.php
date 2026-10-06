<?php

namespace App\Models;

use App\Enums\PropertyType;
use Database\Factories\SavedSearchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $max_price
 * @property int|null $min_bedrooms
 * @property PropertyType|null $property_type
 * @property string|null $region
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read User $user
 */
class SavedSearch extends Model
{
    /** @use HasFactory<SavedSearchFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'max_price',
        'min_bedrooms',
        'property_type',
        'region',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'max_price' => 'integer',
            'min_bedrooms' => 'integer',
            'property_type' => PropertyType::class,
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Does the given listing satisfy every criterion on this saved search?
     * Null criteria are wildcards — they do not restrict the match.
     */
    public function matches(Listing $listing): bool
    {
        if ($this->max_price !== null && $listing->price > $this->max_price) {
            return false;
        }

        if ($this->min_bedrooms !== null && $listing->bedrooms < $this->min_bedrooms) {
            return false;
        }

        if ($this->property_type !== null && $listing->property_type !== $this->property_type) {
            return false;
        }

        if ($this->region !== null && $listing->branch->region !== $this->region) {
            return false;
        }

        return true;
    }
}
