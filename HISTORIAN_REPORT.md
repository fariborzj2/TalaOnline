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
