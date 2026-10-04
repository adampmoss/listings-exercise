# Saved-Search Alerts — Implementation Notes

## Architecture

A **SavedSearch** belongs to a User and stores search criteria as explicit database columns (`max_price`, `min_bedrooms`, `property_type`, `region`). Explicit columns were chosen over a JSON blob because the criteria are small, well-defined, and benefit from straightforward validation and queryability.

Matching logic lives on the model (`SavedSearch::matches(Listing): bool`), separate from any controller or HTTP concern. It mirrors the filter semantics already used by `ListingController`: `price <=`, `bedrooms >=`, exact match on type and region. Null criteria act as wildcards.

When a listing transitions to live, a `ListingObserver` dispatches a `ListingBecameLive` event. A `GenerateSavedSearchAlerts` listener handles the event: it iterates saved searches, applies `matches()`, and creates an **Alert** record for each user whose search is satisfied. Alerts are unique per user per listing (enforced by a database constraint), so the process is idempotent.

```
Listing saved (created as live, or status → live)
  → ListingObserver dispatches ListingBecameLive
  → GenerateSavedSearchAlerts listener
    → iterates saved searches
    → SavedSearch::matches(listing)
    → Alert::firstOrCreate (deduplicated by user + listing)
```

The observer checks `wasChanged('status')` and compares against the original value, so editing an already-live listing does not re-fire.

## Product decisions

- **No backfill.** Alerts are only generated for listings that become live *after* a search is saved. Existing live listings are discoverable through normal search — flooding the alerts list on save would be noisy.
- **One alert per user per listing.** If a listing matches two of a user's saved searches, they get one alert (linked to the first matching search). The unique constraint on `[user_id, listing_id]` enforces this.
- **Null = no restriction.** A saved search with only `region: Manchester` matches any price, bedroom count, or property type in Manchester.
- **Criteria are deliberately limited** to what the task specifies. A price *range* (min + max), post code radius, or garden/parking flags are natural extensions but would be scope creep here.

## Production considerations

Matching currently runs synchronously inside the listener. This is appropriate for the exercise but would not scale to millions of saved searches.

At production scale:

1. `ListingBecameLive` would dispatch a **queued job**.
2. The job would **narrow candidates in SQL** using indexed criteria columns (`WHERE max_price IS NULL OR max_price >= ?`, etc.) rather than loading every saved search into memory.
3. Alerts would be **bulk-inserted** with `INSERT ... ON CONFLICT IGNORE` instead of per-row `firstOrCreate`.
4. A second job would **dispatch email/push notifications** asynchronously, respecting user preferences and digest frequency.

The database uniqueness constraint makes alert generation safe to retry — a failed or duplicated job cannot produce duplicate alerts.

## What I'd do with more time

- **Mark-as-read endpoint** — the `read_at` column is in the schema but not yet exposed.
- **Notification delivery** — wire up Laravel notifications (database + mail channels) behind the alert creation.
- **Alert count in nav** — share an unread-alert count via the Inertia `HandleInertiaRequests` middleware.
- **Save-from-search UX** — a "Save this search" button on the listings index that pre-fills criteria from the current filters.
- **Richer Vue pages** — the saved-search and alert pages are functional stubs; a real UI would deserve more attention.

## Tests (53 total)

| Area | What's covered |
|------|----------------|
| **SavedSearch CRUD** | Create with full/partial criteria, validation (invalid type, negative price, missing criteria), delete own, 403 on another user's |
| **Matching** | Each criterion matching and not matching, boundary equality (price = max, bedrooms = min), null wildcards, partial criteria, mixed pass/fail |
| **Alert generation** | Draft→live creates alert, non-matching search skipped, draft doesn't trigger, editing already-live listing doesn't re-trigger, created-as-live triggers, duplicate dedup across overlapping searches, correct user scoping |
| **Alert viewing** | Index shows own alerts only, hides other users', includes nested listing + branch data, paginated, newest-first ordering |
