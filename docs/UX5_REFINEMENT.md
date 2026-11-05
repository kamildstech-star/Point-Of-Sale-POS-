# UX-5 — Accessibility and performance refinements

**Date:** 2026-11-05 · **Authorization:** user approved UX-4 and explicitly authorized UX-5 accessibility/performance work.  
**Plan:** [UI/UX refinement plan, §10 UX-5](UNIVERSAL_POS_UI_UX_REFINEMENT_IMPLEMENTATION_PLAN.md).  
**Review:** [Screenshot gallery](design/refinement/ux5/index.html) · [Verification report](audit/refinement-ux5-verification.json).

## Audit and implementation

| Finding | Change | Verification |
| --- | --- | --- |
| Client-side navigation kept focus on the previous navigation control and left a generic browser title. | Shared `RouteAccessibility` updates the title and focuses the actual page heading after page/business changes. It waits through asynchronous loading. | Initial-entry, navigation, delayed page, business switch, query-only update and active-dialog tests; real Chrome keyboard checks. |
| Late page loading could interrupt someone who had already started working. | Pointer/keyboard activity cancels pending automatic focus. Initial entry preserves scanner/form autofocus, while query filters and pagination keep reading position. | Input preservation and asynchronous-load tests; existing POS focus/shortcut tests. |
| A dialog opened before its reference data arrived had no field to focus. | Modal observes the arriving form and focuses its first available field once, unless the user has already started navigating. | New asynchronous-dialog tests plus actual Add product form loading in Chrome. |
| Nested disclosures and CSS-hidden actions could incorrectly enter modal wraparound. | Shared focus discovery excludes hidden ancestors and summaries inside closed outer disclosures. | Nested disclosure, hidden-action, focus trap, Escape, nested-modal and focus-restoration tests. |
| New server-page results had no screen-reader announcement. | Named, atomic, polite pagination status announces the server summary. `aria-busy` defers count updates during loading while the existing loading status describes the request. | Pagination/loading tests and browser semantic checks. |
| The toast countdown remained visibly animated under reduced motion. | Reduced-motion CSS hides the moving progress bar while retaining the animation event used by Toastify's dismissal timer. Existing static skeleton/spinner behavior is preserved. | Real reduced-motion media emulation with active loaders and a validation toast. |
| Heavy management pages were included in initial JavaScript. | Authorized route pages use `React.lazy` and the shared Suspense loading pattern. Permission checks remain outside those page components. | Production asset comparison, browser asset-request inspection and route/access regression tests. |
| Applying unchanged reporting dates reloaded the context and every dashboard widget. | Equivalent normalized filters are a no-op. Deliberate Refresh still reloads access and all widgets. | Dashboard request-count tests and Chrome HTTP fixture logs. |
| A canceled context request could dispatch unnecessary widget requests if its adapter resolved late. | The dashboard loader checks cancellation immediately after context resolves. | Deferred/canceled context regression test. |
| Unchanged table resize measurements scheduled unnecessary state updates. | `TableViewport` retains its state when horizontal/vertical overflow is unchanged. | React Profiler test verifies no extra commit for identical measurements and a real update when overflow changes. |

## Navigation and screen-reader behavior

Protected workspace pages receive titles such as `Products · Universal POS` or `Business Owner Dashboard · Universal POS`. Sign-in and account-registration routes also receive appropriate titles. Page changes focus the current page's first real `h1`, with a visible focus ring. Loading placeholders are marked busy and are not treated as the destination heading.

Opening a page directly does not take focus away from the POS scanner or another field. Changing a query filter or server page does not restart page focus or scroll to the top. If the user interacts while a page is loading, the pending focus move is canceled. An active dialog retains control of focus. The existing Skip to content link, permission-filtered navigation and mobile drawer remain in use.

Dialog behavior still prevents background interaction, traps Tab/Shift+Tab, restores the trigger on dismissal and blocks dismissal during a save. Newly arriving fields receive focus only while the dialog itself still owns focus and the user has not begun another interaction. Field errors, top-of-form summaries and entered values remain intact.

Screen-reader validation uses semantic DOM checks and Chrome's accessibility tree for named modal isolation. This is not a native NVDA/JAWS/VoiceOver usability session or a claim of complete WCAG certification. See the manual checks below before release.

## Performance and request boundaries

The actual UX-4 production build was captured before rebuilding. Its initial JS asset was **490,660 bytes**. UX-5 moves management route modules into on-demand chunks; the final initial asset is **416,871 bytes**, saving **73,789 bytes (15.04%)**. Exact assets and source hashes are recorded in [the verification report](audit/refinement-ux5-verification.json). This is a bundle-size improvement, not a measured claim about user-network latency or server response time.

The browser checks that unopened inventory, purchasing, billing, reports and team page chunks are not downloaded on initial dashboard entry. Catalog lists still request one server page and display the returned page; this work does not introduce client-side loading of complete tables.

Dashboard requests continue to use verified tenant context, server capabilities and permissions. Context is revalidated on Refresh and real scope changes. Snapshot tenant, branch, policy, dates, currency, timezone, personal-sales scope and data revision checks are preserved. Paginated dashboard refreshes still reload the coherent widget set rather than mixing cached figures with a newer page. No client cache reuses financial responses across users, roles, tenants or branches.

Checkout pricing, payment requests, expiry policy and same-request retry/idempotency behavior are unchanged. POS keeps its Comfortable density and existing F2 safeguards. The browser regression runs exercise expiry, known rejection, uncertain outcome and retry with the same request body.

## Files changed

- `src/components/layout/RouteAccessibility.jsx`, `src/layouts/TenantLayout.jsx`: shared page titles and focus lifecycle.
- `src/app/App.jsx`: lazy route modules, shared busy fallback and public authentication-page titles.
- `src/components/Modal.jsx`: asynchronous initial focus and accurate hidden/disclosure handling.
- `src/components/ui/ListPatterns.jsx`, `src/components/ui/TableViewport.jsx`: accessible result summaries and unchanged-measurement bailout.
- `src/features/dashboard/dashboardData.js`, `WorkspaceOverview.jsx`, `useBusinessDashboard.js`: no-op equivalent filters and early cancellation, preserving scope validation.
- `src/styles/shell.css`, `src/styles/notifications.css`: heading focus visibility and reduced-motion toast feedback.
- `src/test/Accessibility.test.jsx`, existing business/loading/auth tests, test setup: behavioral regressions and jsdom scrolling shim.
- `scripts/refinement-ux5-*.mjs`, `scripts/record-ux5-verification.mjs`: repeatable synthetic browser checks and evidence/gallery generation.

No runtime dependency, backend API, database migration, permission grant or financial rule was added.

## Validation and evidence

Final verification passed **278 React tests across 24 files**, **19 focused accessibility/modal tests**, **246 Chrome checks** (56 accessibility, 144 role/scope, 46 checkout) and the production build. The gallery contains **81 current screenshots** and **eight actual UX-4 baseline captures**; all **180 gallery links** were checked. These totals and source hashes are recorded in [refinement-ux5-verification.json](audit/refinement-ux5-verification.json). Browser captures serve the built app against local synthetic HTTP fixtures; they do not write to the working POS database.

The browser matrix covers light/dark themes, keyboard navigation and dialogs, 360/768/1280/1440/1920 CSS-pixel widths, equivalent 125%/200% zoom viewports, reduced motion, authorized dashboard profiles and checkout states. Screenshots show synthetic data. Existing UX-1–UX-4 evidence is preserved.

No Laravel source changed in UX-5. The prior UX-4 backend regression remains available: [48 passing tests / 1,161 assertions](UX4_REFINEMENT.md). UX-5 does not claim that those backend tests were run again.

### Reproduce

From `POS Frontend`:

```powershell
npm.cmd test -- --testTimeout=20000 --reporter=json --outputFile=../docs/audit/private/refinement-ux5-vitest.json
npm.cmd test -- --testTimeout=20000 --reporter=json --outputFile=../docs/audit/private/refinement-ux5-accessibility-vitest.json src/test/Accessibility.test.jsx src/test/Modal.test.jsx
npm.cmd run build
node scripts/refinement-ux5-browser-smoke.mjs
node scripts/refinement-ux5-role-browser-smoke.mjs
node scripts/refinement-ux5-pos-browser-smoke.mjs
node scripts/record-ux5-verification.mjs
```

The browser script's `--before` option captured the real approved UX-4 build. Do not use it against a new build to replace historical baselines.

Twelve corresponding human/device acceptance scenarios, **UX5-01–UX5-12**, were added to [the UAT checklist](UAT_TEST_CASES.csv). Their manual status remains Not Run; automated evidence is recorded separately.

## Manual release checks

1. With keyboard only, use Skip to content, navigate to Products and open Add product. Wait for reference data; the first field should receive focus. Navigate away while a page loads and confirm focus is not pulled back later.
2. Open optional/nested form sections with Enter/Space. Tab and Shift+Tab should reach visible controls only. Escape closes an idle dialog and returns to its trigger. In-flight saves retain their protection.
3. With NVDA/JAWS/VoiceOver, confirm the page title/heading after navigation, named dialog and hidden background, linked validation errors and server-page result announcements. Confirm loading and completion messages are useful without excessive repetition.
4. At actual browser zoom 125% and 200%, review lists, long identifiers, full amounts and modal footer actions. Try a touch device/coarse pointer as well as desktop keyboard.
5. Enable the OS reduced-motion preference. Load a delayed list and trigger validation. Loaders should be static and the toast countdown should be hidden; toast dismissal, keyboard controls and inline errors must still work.
6. Open browser Network tools: unused management pages should not download on dashboard entry. Applying the displayed dates again should make no new requests; Refresh must still reload context and widgets.
7. In an authorized test business, verify POS initial scanner focus, F2 outside editable controls, touch sizes, quote expiry and completion. Use synthetic automated fixtures to review unconfirmed/rejected cases without creating working transactions.
8. Recheck restricted branches, personal-sales scope and permission revocation before production rollout. Review real device/scanner and assistive-technology behavior in the deployment environment.

## Scope and review

The approved UX-5 slice is accessibility/performance. New persisted table preferences, favorites, configurable widgets and an action center have not been approved as product requirements and are not introduced. Existing theme/sidebar preferences continue through their existing providers. Separate dashboard D5–D7 work and staging/production release gates remain separate.

This completes the authorized UX-0–UX-5 refinement sequence for review. Native assistive-technology, real-device and production-network checks remain release validation activities; no staging server is configured.
