# Notes

## Events

As someone who has worked with an entore EDA in a previous role I was very keen to make the alert generation dependent on a real event "ListingUpdated". This is based on a created() or updated() observer method by a listener called ListingObserver. The conditions are:

- Status _was_ changed
- Listing status is now "Live"
- The status before was NOT live

I also love events because in EDA this opens up the possibility in the future for other cool things to happen when the event occurs such as hooking in different notification types or triggering Analytics events.

The requirement of the task mentioned “a listing became live” as a domain concept, so a domain event was the right fit for this. It's set up using `QUEUE_CONNECTION=sync` so the listener will do the job straight away, but this could easily become a queue job (SQS or Laravel Queue driver) and would certainly do this at scale.

The resulting matches are persisted in the alerts table. If we later wanted email notifications, I'd probably dispatch separate queued jobs from newly created alerts, and track delivery separately from read_at, because read state and notification-delivery state are different concerns."

## Models & Relationships

True to the current system architecture I created a new model for "SavedSearch" and "Alert", a user can have many of each and both `belongTo` a User.

### SavedSearch

I used separate columns for the search criteria because there are only four fields with clearly defined types. It also makes the SavedSearch data queryable, whereas a data column containing a JSON string would require much more processing when querying.

### Alert

An alert is a very simple model/DB structure with foreign keys for user ID, savedSearch ID and listing ID. An alert can never be duplicated because I protect it in two ways: firstOrCreate() checks for an existing user + listing combination, and there's also a DB constraint on user_id and listing_id. No PII in this table is a deliberate choice.

Alerts are unique per user per listing (enforced by the DB constraint), so the process is idempotent.

## Tradeoffs & Decisions

- No backfill. Alerts are only generated for listings that become live after a search is saved. Existing live listings are discoverable through normal search — flooding the alerts list on save would be noisy.
- One alert per user per listing. If a listing matches two of a user's saved searches, they get one alert (linked to the first matching search). The unique constraint on [user_id, listing_id] enforces this.
- Null = no restriction. A saved search with only region: Manchester matches any price, bedroom count, or property type in Manchester.
- Criteria are deliberately limited to what the task specifies. A price range (min + max) would be much better for users, post code radius instead of a hardcoded region, or garden/parking flags are natural extensions but would be scope creep here.

One tradeoff is that this code doesn't deal with a listing being updated, only when it goes live. So if a house drops in value into someones criteria range it would not trigger the observer. This would be a potential improvement to make.

The biggest risk to scaling is the SavedSearc::query()->cursor() which could potentially have millions of records, to run the matches() function against - this could run many times a day. I used matches() for this exercise for simplicity, but in a production environment would pass the matching logic to SQL, e.g:

```
SavedSearch::query()
    ->where(fn ($q) =>
        $q->whereNull('max_price')
          ->orWhere('max_price', '>=', $listing->price)
    )
    // other criteria...
    ->cursor();
```

## Alert processing — next steps

A second job would dispatch email/push notifications asynchronously, respecting user preferences and digest frequency.

The alerts table currently serves as both a record of matches and the user-facing alert list. In production it would also act as a staging area for delivery:

1. A scheduled worker queries alerts that have not yet been delivered (a notified_at column or a separate alert_notifications table would track this).
2. The worker batches alerts per user into a digest to avoid spamming — the support team's concern ("don't spam people") is a real constraint.
3. Each batch is dispatched as a queued notification (Laravel's mail and/or database channels), decoupling delivery from generation so failures can be retried independently.
4. The database uniqueness constraint on [user_id, listing_id] means the generation side is idempotent, so a retried or duplicated job cannot produce duplicate alerts, so the worker only needs to track delivery state.
