
## 2025-05-14 - Improve Dashboard UX with Skeletons and Trends
- Learning: Placeholders like "---" are jarring and break the visual flow of a dashboard during loading. Skeleton screens provide a smoother transition and better perceived performance.
- Learning: Relying solely on color for price trends is an accessibility anti-pattern. Adding icons ensures readability for color-blind users.
- Action: Implemented skeleton loading shimmer effect and CSS-based trend arrows across the dashboard.
- Action: Unified numerical formatting using browser-native 'Intl' API for robust Persian localization.
