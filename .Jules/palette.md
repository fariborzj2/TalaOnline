
## 2025-05-14 - Improve Dashboard UX with Skeletons and Trends
- Learning: Placeholders like "---" are jarring and break the visual flow of a dashboard during loading. Skeleton screens provide a smoother transition and better perceived performance.
- Learning: Relying solely on color for price trends is an accessibility anti-pattern. Adding icons ensures readability for color-blind users.
- Action: Implemented skeleton loading shimmer effect and CSS-based trend arrows across the dashboard.
- Action: Unified numerical formatting using browser-native 'Intl' API for robust Persian localization.

## 2025-05-14 - Refined Typography and Data Consistency
- Learning: Unitless line-height (e.g., 1.5) is superior to fixed pixel values as it scales naturally with font size, especially important for responsive RTL layouts with varying character heights.
- Learning: Maintaining realistic mock data (like silver vs gold prices) is crucial for trust and professional presentation, even in development stages.
- Action: Converted all fixed line-heights to relative values and corrected silver prices across the dataset.
- Action: Enhanced UI depth with soft shadows and refined sticky navigation for better focus.
