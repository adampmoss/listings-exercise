# Implementation Notes

## Approach

I modelled **a listing becoming live** as a domain event.

A `ListingObserver` detects when a listing is either created as live or transitions from another status to live, and dispatches a `ListingBecameLive` event.

`GenerateSavedSearchAlerts` handles that event, checks the listing against saved searches, and creates an alert for each user with a matching search.

The listener implements `ShouldQueue`. The exercise currently uses `QUEUE_CONNECTION=sync`, so this work happens immediately, but the same design could be moved to a real queue in production without changing the event flow.

This also keeps the listing lifecycle separate from alert generation and gives us a natural extension point for other behaviour in future.

## Saved searches

I introduced a `SavedSearch` model containing the four criteria required by the exercise:

- Maximum price
- Minimum bedrooms
- Property type
- Region

The criteria are stored as individual columns rather than JSON because the fields are known, strongly typed and potentially useful when querying.

A null value means **no restriction** for that criterion. For example, a search containing only `region = Manchester` will match any listing in Manchester regardless of its price, bedroom count or property type.

The matching logic lives in `SavedSearch::matches()`. This keeps the domain rule independently testable and reusable outside the alert listener.

## Alerts

Alerts are persisted as first-class records relating a user, saved search and listing.

There is deliberately only **one alert per user per listing**, even if several of that user's saved searches match the same listing.

Duplicate alerts are protected against at two levels:

- `firstOrCreate()` checks for an existing user/listing combination.
- A database unique constraint on `[user_id, listing_id]` provides the final guarantee.

This also makes alert generation idempotent if the event or queued listener is ever retried.

The alerts page loads the related listing, branch and saved search up front to avoid N+1 queries when rendering the results.

## Authorisation and validation

Saved searches are always queried through the current user, so users only see their own searches and alerts.

Deletion is additionally protected by a `SavedSearchPolicy`.

The save-search request validates each individual criterion and also requires at least one criterion to be supplied, preventing an empty search that would match every listing.

## Product decisions

**No backfill**

Saving a search does not immediately generate alerts for existing live listings. Those properties are already available through the normal search experience. Alerts represent new matching listings becoming available after the search has been saved.

**One alert per listing**

If two saved searches belonging to the same user match the same listing, only one alert is generated. This avoids showing duplicate notifications for the same property.

**Only becoming live triggers an alert**

The current implementation deliberately follows the exercise requirement: alerts are generated when a listing becomes live.

An update to an already-live listing does not currently trigger matching again. For example, if the price of an existing live property later dropped into a user's price range, they would not receive a new alert. Supporting meaningful listing updates would be a useful future extension.

## Scaling considerations

The main limitation of the current implementation is that every saved search is considered when a listing becomes live:

```php
SavedSearch::query()->cursor()
```

Using `cursor()` avoids loading the entire collection into PHP memory at once, but it still means every saved search must eventually be evaluated.

That is reasonable for this exercise, but would not scale to millions of saved searches.

At production scale I would move the initial filtering into the database so that only likely matches are returned, for example:

```php
SavedSearch::query()
    ->where(fn ($query) =>
        $query->whereNull('max_price')
            ->orWhere('max_price', '>=', $listing->price)
    )
    // Apply equivalent filters for the other criteria...
    ->cursor();
```

The existing `matches()` method could then still be used against the smaller candidate set as the final domain check.

Alert generation could also run asynchronously through a production queue such as SQS.

## Further improvements

Given more time, useful extensions would include:

- Alerting when meaningful changes to an existing listing cause it to newly match a saved search.
- Email or push notifications, potentially grouped into digests to avoid excessive notifications.
- Moving more of the candidate matching into SQL as the number of saved searches grows.
- More flexible search criteria such as minimum/maximum price ranges and location-radius searches.

These have deliberately been left outside the implementation to keep the solution focused on the requirements of the exercise.
