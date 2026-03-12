⚡ HyperBolt X: User Profile Stats Batching

BOTTLENECK
The user profile route (`includes/routes.php`) executed three sequential `COUNT(*)` database queries to determine the user's `follower_count`, `following_count`, and `is_following` status. This resulted in redundant network I/O and database engine overhead on every profile view.

ROOT CAUSE
The queries were written imperatively rather than relationally, treating the database as an iterative data source rather than leveraging its aggregation capabilities.

OPTIMIZATION
Implemented conditional aggregation using `SUM(CASE WHEN ... THEN 1 ELSE 0 END)` and `MAX(CASE WHEN ... THEN 1 ELSE 0 END)`. This consolidated the 3 distinct queries into a single batched database round-trip that scans the `follows` table once per user profile load.

EXPECTED IMPACT
• database queries reduced 3 -> 1 per profile view
• network overhead reduced significantly
• CPU cost of database engine context switching eliminated for two queries

SAFETY
The optimization maintains identical functionality. Edge cases for `viewer_id` were guarded against by passing a `-1` placeholder for the active viewer when not authenticated to ensure PDO strictly binds an integer and avoids unexpected evaluation. The returned types are heavily casted (`(int)`) to preserve consistency in the UI views.

VERIFICATION
Engineers can confirm the improvement by inspecting the active query logs or observing the output of `follower_count` and `following_count` rendering normally with a single read execution in the `includes/routes.php` user profile handler.

## YYYY-MM-DD — Insight

⚡ HyperBolt X: Post Edit Tag Sync Optimization

BOTTLENECK
The `site/admin/post_edit.php` file executed an N+1 database query when saving or updating a blog post with tags. For every tag name provided by the user, the script executed a `SELECT id FROM blog_tags WHERE name = ?` query inside a `foreach` loop.

ROOT CAUSE
The logic to map tag names to tag IDs was written imperatively, querying the database individually for each tag in the submission rather than leveraging SQL's set-based capabilities.

OPTIMIZATION
Replaced the iterative querying with a single batched `SELECT id, name FROM blog_tags WHERE name IN (?, ?, ...)` query. The retrieved tags are then mapped in PHP, drastically reducing database round-trips. Existing tags are found in the pre-fetched map, and only missing tags require new `INSERT` and `SELECT` operations.

EXPECTED IMPACT
• database queries reduced from N to 1 (where N is the number of existing tags in the post)
• CPU work and database connection overhead significantly reduced during post submission

SAFETY
The optimization preserves identical behavior. Case-insensitivity in tag lookup is maintained by using `array_change_key_case` with `CASE_LOWER` and `strtolower` in PHP, matching typical database collation behavior. An `if (!empty($tags_array))` check was added to guard the `IN (...)` query against SQL syntax errors when the tags list is empty.

VERIFICATION
Engineers can confirm the improvement by submitting a post with multiple existing tags and observing the database query logs. The logs will show a single `SELECT ... WHERE name IN (...)` query instead of multiple single-tag lookups.

Learning:
Iterative database queries inside loops during form submissions are a common bottleneck. Always attempt to pre-fetch required relational data using batched `IN` queries before entering processing loops.

Action:
Future agents should look for similar N+1 query patterns in form handlers and synchronization logic (e.g., categorizing items, mapping relations) and replace them with batched pre-fetching strategies.

## YYYY-MM-DD — Insight

⚡ HyperBolt X: PushService N+1 Optimization (getUserSettings)

BOTTLENECK
The `PushService::notify` method dynamically fetched user notification settings via `PushService::getUserSettings` (`SELECT * FROM notification_settings WHERE user_id = ?`). However, in `TriggerEngine`, many events (like market anomalies or new blog posts) target tens or hundreds of users in a single loop (`foreach ($users as $user_id)`). This caused a severe N+1 query problem, hitting the database once per notified user.

ROOT CAUSE
The `getUserSettings` method was designed for a single user context and did not natively support batch loading. The calling loops iterated imperatively without giving the underlying service a chance to pre-fetch relationships or configs.

OPTIMIZATION
Introduced a `preloadUserSettings(array $user_ids)` method in `PushService` that fetches settings for all provided users in a single batched `IN (...)` query. It caches the results (and safely marks non-existent settings as `false` to avoid re-fetching). `TriggerEngine` was updated to call this preload method before iterating over user arrays in bulk events. `getUserSettings` now returns from cache immediately.

EXPECTED IMPACT
• database queries per bulk notification event reduced from N to 1 (where N is the number of targeted users)
• significant reduction in database connection and processing overhead during background worker execution
• improved multi-channel notification throughput

SAFETY
Identical behavior is preserved. The `preloadUserSettings` checks which IDs are not yet in cache, fetching only the missing ones. Missing rows are assigned `false` in the array so `getUserSettings` still correctly provides the default structure, avoiding infinite query loops or missing defaults.

VERIFICATION
When `handleMarketAnomaly` or other bulk triggers fire, the system logs will show a single `SELECT * FROM notification_settings WHERE user_id IN (...)` rather than sequentially polling for every user.

Learning:
N+1 query patterns aren't just limited to ORMs fetching related entities; they often hide in service classes that perform granular checks (like configurations, permissions, or settings) during iterative processes.

Action:
When designing services used within loops (like `notify`, `checkAccess`, `calculateScore`), always provide a `preload` or batched API method to hydrate in-memory caches before iteration begins.

## YYYY-MM-DD — Insight

⚡ HyperBolt X: Prices API Query Batching

BOTTLENECK
In `site/api/prices.php`, when no specific symbol was requested, the endpoint sequentially executed two nearly identical database queries to fetch the historical data for `18ayar` and `silver` to populate the default dashboard charts.

ROOT CAUSE
The initial implementation favored simplicity, separating the concerns for fetching `gold` vs `silver` into distinct queries rather than utilizing an SQL `IN` clause to retrieve multiple targeted subsets simultaneously.

OPTIMIZATION
Replaced the sequential queries with a single batched query using `WHERE symbol IN ('18ayar', 'silver')`. The returned dataset is dynamically grouped back into the required `gold` and `silver` JSON response structure during the iteration over the result set.

EXPECTED IMPACT
• database queries reduced 2 -> 1 on default chart API load
• database overhead and network roundtrips minimized

SAFETY
The optimization guarantees identical JSON output. The `ORDER BY date ASC` applies universally to both symbols in the result set, ensuring time-series integrity when grouping in PHP.

VERIFICATION
Engineers can request `site/api/prices.php` without parameters and confirm the `gold` and `silver` objects still contain correctly formatted arrays of dates and prices.

Learning:
When multiple independent queries target the same table with similar constraints but different identifier targets, grouping them via an `IN` clause and handling the data separation in the application layer usually yields better performance.

Action:
Consolidate sequential identical data fetches into batched lookups where appropriate.
