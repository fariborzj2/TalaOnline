
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

## 2025-05-14 - Advanced Table Interactivity
- Learning: For long comparison tables, vertical scrolling with sticky headers is essential to maintain column context without losing the page's overall structure.
- Learning: Client-side sorting for Persian content requires careful handling of numeric vs. string data, using 'localeCompare' for strings and standard arithmetic for numeric types.
- Action: Implemented professional sorting functionality with visual indicators and enabled internal table scrolling with a fixed max-height.

## 2025-05-14 - Aesthetic Scrollbar Integration
- Learning: Default browser scrollbars often clash with modern, minimalist dashboard designs. Customizing them with subtle colors and rounded corners enhances the premium feel. Moving the scrollbar from the container to the `tbody` specifically (using a flex-based table layout) provides a cleaner interface where the header remains perfectly stationary while the content scrolls.
- Action: Added a custom-styled scrollbar specifically for the `tbody` of the platforms table, using theme-consistent colors and hover effects for a better micro-UX.

## 2025-05-14 - Table Layout Precision
- Learning: In data-dense comparison tables, allowing text to wrap can lead to vertically bloated rows that break alignment and scanning efficiency. Forcing 'white-space: nowrap' ensures a clean, predictable layout.
- Action: Applied 'white-space: nowrap' to all table cells to maintain single-line data integrity.

## 2025-05-14 - Professional Error Handling
- Learning: Error states should be as polished as the rest of the application. A simple red banner is often too aggressive or jarring. A well-designed error card with an icon and a clear call-to-action (like a 'Reload' button) reduces user frustration.
- Action: Refactored the error banner into a professional centered container with a reload mechanism in 'js/app.js'.

## 2025-02-01 - Sticky Table Columns in RTL
- **Learning:** Using 'overflow-y: auto' on 'tbody' breaks 'position: sticky' on table cells ('th'/'td') because 'sticky' requires the scrolling ancestor to contain the entire table structure. In RTL layouts, 'right: 0' must be used for horizontal stickiness.
- **Action:** Refactored the table to use a single container ('.table-box') for both horizontal and vertical scrolling, and converted table rows to flexbox ('display: flex') to maintain alignment when cells are sticky.
