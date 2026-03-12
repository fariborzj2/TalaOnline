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

## YYYY-MM-DD — Insight

⚡ HyperBolt X: Role Management Correlated Subquery Optimization

BOTTLENECK
In `site/admin/roles.php`, the primary data fetching query used a correlated subquery in the `SELECT` clause to count the number of users assigned to each role: `SELECT r.*, (SELECT COUNT(*) FROM users u WHERE u.role_id = r.id) as users_count FROM roles r`. This architecture forces the database engine to execute the inner `SELECT COUNT(*)` repeatedly for every single row returned by the outer query, creating a hidden N+1 bottleneck within the database itself.

ROOT CAUSE
The query was written using a scalar subquery approach rather than leveraging standard relational aggregation (JOIN and GROUP BY).

OPTIMIZATION
Refactored the SQL query to utilize a `LEFT JOIN` combined with a `GROUP BY` clause: `SELECT r.*, COUNT(u.id) as users_count FROM roles r LEFT JOIN users u ON u.role_id = r.id GROUP BY r.id ORDER BY r.id ASC`.

EXPECTED IMPACT
• database query execution plan simplified from O(N) internal sub-executions to a single, optimized relational hash/merge join
• decreased CPU overhead on the database engine
• consistent query time regardless of the number of roles scaling up in the future

SAFETY
The optimization guarantees identical output. By using `LEFT JOIN` instead of `INNER JOIN`, roles with zero users are still returned in the result set. `COUNT(u.id)` correctly returns `0` for roles with no users, preserving the identical numeric response expected by the frontend table view.

VERIFICATION
Engineers can visit the `site/admin/roles.php` dashboard and verify that the "تعداد کاربران" (Number of Users) column still accurately reflects the total user count for each role without missing any empty roles.

Learning:
Correlated subqueries in the `SELECT` clause are often structural anti-patterns that mask N+1 execution inside the database engine. They should generally be rewritten into `LEFT JOIN`s with aggregation.

Action:
Future agents should actively `grep` for the pattern `SELECT.*(SELECT` to detect and eliminate database-level hidden N+1 queries across the admin dashboards.

## YYYY-MM-DD — Insight

⚡ HyperBolt X: Email Queue Status Batching Optimization

BOTTLENECK
In `site/admin/email_settings.php`, the system executed three separate, sequential `COUNT(*)` queries against the `email_queue` table to calculate the number of pending, sent, and failed emails. This resulted in redundant network round-trips and multiple full-table scans (or index scans) by the database engine on every settings page load.

ROOT CAUSE
The initial implementation treated each status metric as an isolated data request, overlooking the database's native conditional aggregation capabilities.

OPTIMIZATION
Refactored the three distinct queries into a single batched query using conditional aggregation (`SUM(CASE WHEN ... THEN 1 ELSE 0 END)`). This evaluates all three status conditions in a single pass over the table.

EXPECTED IMPACT
• database queries reduced from 3 -> 1 per settings page load
• network overhead and I/O wait times minimized
• database engine context switching and scanning overhead significantly reduced

SAFETY
The optimization preserves identical functionality. The results are fetched as an associative array and cleanly extracted with `?? 0` fallbacks, explicitly cast back to integers. This guarantees that the UI rendering logic continues to receive the exact same variable types and values as before, without regressions.

VERIFICATION
Engineers can load the `site/admin/email_settings.php` dashboard and confirm that the queue status widgets (Pending, Sent, Failed) correctly reflect the underlying table counts using only a single executed query.

Learning:
Sequential `COUNT(*)` queries on the same table with varying `WHERE` conditions are a classic backend bottleneck. They can almost always be optimized into a single scan using conditional aggregation.

Action:
Future performance sweeps should `grep` for adjacent `COUNT(*)` statements or repeated `COUNT` queries over the same table in admin dashboards, unifying them via `SUM(CASE WHEN...)`.
