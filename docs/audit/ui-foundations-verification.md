# UI-1 verification — 2026-11-02

Scope: [UI-0 audit](../UI_FOUNDATIONS_AUDIT.md) and [UI-1 foundations](../UI_FOUNDATIONS.md), following the supplied guide. React presentation only; no Laravel/database/environment changes.

## Automated checks

- `npm.cmd test`: **139 tests passed across 12 files**. Nine new theme/foundation cases cover light default despite OS dark preference, keyboard toggle and remount persistence, different staff/guest preferences, unchanged draft values on presentation rerenders, cross-tab synchronization, invalid preferences, unavailable browser storage, unchanged decimal-string form submission, and native/status semantics.
- Existing tenant-switch test additionally checks that theme persists while business permissions refresh.
- Existing tests continue to cover authentication, authorization-aware navigation, catalog/CRUD, client/server validation, popup focus/busy behavior, notification hosting, scanner input, quote/checkout idempotency, split payments, returns, cash closing and subscription UI.
- `npm.cmd run build`: passed, including generated local Remix assets for 107 named icons and Vite production compilation. Tailwind 4.3.3 and Inter 5.3.0 are pinned; installed Vite is 6.4.4.
- No `.ts`/`.tsx` application source or Lucide imports/dependency introduced. Component colors are CSS variables; literal palette values live in `tokens.css`, with explicit monochrome print overrides.

The full frontend suite passed after the JavaScript changes. The final label/lookup spacing adjustment changes CSS only and is verified by the production build and browser suite. No backend tests were rerun because backend/API/calculation code was unchanged.

## Built-application browser checks

Headless Chrome loads Vite's production output against an isolated local HTTP fixture with synthetic owner, product and category records. The fixture does not connect to Laravel or the POS database. This verifies browser behavior and frontend request shapes; it is not an end-to-end deployment or database integration test.

- New profile starts light; Inter loads locally with Google font hosts blocked.
- Toggle changes the expected root class, native color scheme, background, surfaces and actions. Navigation links and routes remain identical.
- Full-page reload restores the preference. Switching fixture staff IDs uses separate stored preferences and restores the original user's choice.
- Native Enter activates the theme button with keyboard focus retained and a visible focus outline.
- A cross-tab preference event themes an open product dialog without resetting the unsaved product name, active field or focus containment.
- Dialog bounds pass at 834, 720, 390 and 320 CSS-pixel widths. A 720×500 CSS layout viewport rendered at device scale 2 checks the reflow equivalent of a 1440×1000 display at 200% zoom; this is viewport emulation, not a browser zoom-menu test.
- Representative visible text in page/table/sidebar/dialog/native-control states meets 4.5:1 contrast. Measured minimum: **5.12:1 light**, **6.96:1 dark**. Native control/focus/button token pairs are separately documented in the foundation guide. This sampling is not a complete WCAG audit of every page/state.
- Existing regression checks pass: client-required/decimal validation, API 422 field associations, focus on first invalid control, popup Tab containment/Escape restoration, accessible modal error toasts, success toast surviving modal closure, exact single create/update/archive requests, cancel without deletion, price string `12.3456` retained, category slug preserved, and mobile popup bounds.
- Existing sidebar checks pass: fixed position on both page-scroll axes, capped height, short-menu content height, internal scrolling to the final link, compact sticky mobile navigation, and print without the navigation column.
- Existing legacy checks pass: actual jQuery/AdminLTE collapse/expand and click listeners still work with local Remix assets.
- No browser runtime exceptions in the successful run.

Reviewable [browser evidence](ui-foundations-browser.json) records the results without request logs. Private detailed output: `docs/audit/private/modal-browser-verification.json`; harness: `docs/audit/private/modal-browser-smoke.mjs`. The harness uses a fresh document marker for reload waits and Windows Enter key text, avoiding measuring stale DOM or incomplete keyboard emulation.

## Reviewable screenshots

These are screenshots of the implemented application using synthetic fixture data, not design mockups.

| State | Preview |
| --- | --- |
| Light product list | [light-products.png](../design/ui1/light-products.png) |
| Dark product list | [dark-products.png](../design/ui1/dark-products.png) |
| Light product dialog | [light-dialog.png](../design/ui1/light-dialog.png) |
| Dark product dialog | [dark-dialog.png](../design/ui1/dark-dialog.png) |
| Dark narrow mobile dialog | [dark-mobile.png](../design/ui1/dark-mobile.png) |
| Dark 200% zoom-equivalent viewport | [dark-zoom.png](../design/ui1/dark-zoom.png) |

UI-2–UI-7, all-route design acceptance, actual barcode hardware/tablet testing, assistive-technology testing, HTTPS staging and production release remain subsequent work.
