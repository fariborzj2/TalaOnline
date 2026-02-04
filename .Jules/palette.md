## 2025-05-12 - Responsive Typography with Clamp
**Learning:** Using `clamp()` for font sizes allows for a seamless transition between mobile and 4K resolutions without relying on excessive media queries. It ensures readability on ultra-wide monitors where standard 'responsive' designs often feel too small.
**Action:** Use `clamp(min, preferred, max)` for all primary typography classes and define a 4K breakpoint (e.g., 2500px) to scale up base font size and container widths.

## 2025-05-12 - RTL Support in Charts
**Learning:** External charting libraries like ApexCharts often default to LTR. RTL support requires manual configuration of `direction: rtl` in tooltips, `text-align: right` for labels, and reversing markers if necessary.
**Action:** Explicitly set `fontFamily` in chart options to match the UI and use CSS overrides for library-generated tooltips to ensure consistent RTL behavior.
