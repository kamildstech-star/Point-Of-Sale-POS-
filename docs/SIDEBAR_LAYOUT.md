# Sidebar layout

The React workspace sidebar follows the menu height and is capped at the viewport height. It stays pinned on desktop while the document scrolls vertically or horizontally. When the menus exceed the available height, the menu area scrolls independently; the branding and footer remain visible. The main content reserves the sidebar's width so it is not covered.

Dashboard D1, approved by the user, replaces the former mobile horizontal menu bar with a focus-managed drawer below 1280 px. The sticky top bar's Open navigation button opens it; Escape or choosing a destination closes it and restores focus. At desktop widths the sidebar can collapse from 240 px to 72 px, retaining accessible names and tooltips. Printed pages omit navigation and its reserved column. See [DASHBOARD_D1.md](DASHBOARD_D1.md).

The original pinning rules remain in `POS Frontend/src/styles/layout.css`. D1 extends them in `shell.css`, imported afterward; breakpoint handling and collapse state live in `src/layouts/TenantLayout.jsx`. No database changes are required.

Browser acceptance checks use a local fixture API:

Original pinning verification on 2026-11-02: production build and Chrome checks passed at 1440 × 1000, 1440 × 600 and 390 × 844. The approved D1 shell was reverified on 2026-11-03, including pinning, short menus, internal scrolling, collapse persistence, tablet/mobile drawer focus and print behavior. See the [D1 evidence](audit/dashboard-d1-browser.json) and [D1 previews](DASHBOARD_D1.md). Unrelated backend transaction tests were not repeated for this frontend-only work.

- Scroll a long/wide desktop page in both directions; the sidebar remains at the viewport edge.
- Reduce the desktop viewport height and focus the final menu item; it remains reachable inside the menu scroll area.
- Restrict the visible menus; the sidebar fits their content rather than stretching to the page length.
- Scroll a mobile page; the top bar remains visible. Open the drawer, reach its final menu link by keyboard/internal scrolling, then close with Escape and verify focus/document scrolling are restored.
- Open and close forms; popup focus, overlays and validation continue to work above the sidebar.
- Switch to print media; the sidebar column is removed.

Private browser evidence is stored in `docs/audit/private/modal-browser-verification.json`, with screenshots `sidebar-desktop-scroll.png`, `sidebar-short-menu.png` and `sidebar-mobile-scroll.png`. Fixture checks do not write to the working POS database or mark manual UAT results as passed.
