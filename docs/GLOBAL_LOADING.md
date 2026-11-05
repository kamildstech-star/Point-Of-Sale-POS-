# Central loading feedback

The React workspace now has one shared network indicator and reusable contextual loading states. No new dependency or Laravel/database change is required.

## Behavior

- A slim, non-blocking progress bar appears at the top during requests made through the shared Axios client: authentication, lists, pagination, search, references, details, reports, imports/exports and writes.
- Initial data regions use table, form or card skeletons with a named loading message. Refreshes retain the existing records and use a compact inline indicator. Failed refreshes keep previously loaded records alongside the error/retry message.
- Display begins after **180ms**, so fast requests do not flash. Once visible, an indicator stays for at least **300ms**. These timings are presentation choices configured centrally in `src/loading/useDelayedBusy.js`.
- Each network request has its own completion handle. Progress remains while any tracked request is active; success, HTTP/network errors and cancellation finish their handles safely. Reusing a request configuration does not double-wrap its transport.
- CSRF cookie requests are excluded. A request can explicitly opt out for background work; ordinary lists/searches remain tracked by default.
- Progress is indeterminate: it does not show an invented percentage. The loader does not capture focus, cover controls, freeze scrolling, retry requests or change financial submission locking.
- Both themes derive colors from existing semantic tokens. Reduced-motion mode uses a static bar and skeletons. Loading feedback is hidden when printing.
- The global portal moves into the active dialog's accessible layer and returns to the body when it closes. Nested dialogs preserve this behavior without restarting pending requests.

Existing submit-button feedback, validation, errors, toasts and server-confirmed transaction outcomes remain in place. A loading indicator is not a success confirmation.

## Shared files

| File | Purpose |
| --- | --- |
| `src/loading/activity.js` | Concurrent activity store and idempotent completion handles |
| `src/loading/trackAdapter.js` | Axios transport tracking with `finally` cleanup, including errors without `error.config` |
| `src/loading/useDelayedBusy.js` | Shared delay/minimum-visibility policy and timer cleanup |
| `src/loading/host.js` | Stable accessible portal target for dialog layers |
| `src/components/GlobalLoader.jsx` | One app-wide indeterminate indicator, mounted in `main.jsx` |
| `src/components/LoadingState.jsx` | Reusable `table`, `form`, `cards` and `inline` loading states |
| `src/styles/loading.css` | Token-based responsive and reduced-motion presentation |

Existing page loading messages now reuse `LoadingState`: session opening, businesses, catalog/setup, team, legacy mapping, product detail/editor options, shared operational tables, POS reference data, receipts, returns, activation settings and billing. Shared `RemoteTable` covers inventory, purchasing, customers/suppliers, registers, payments, cash, sales, returns, expenses, billing invoices and workspace-tool lists. Search/scanner/report submissions without separate regional loading state use the global indicator and their existing controls.

Coverage is the React workspace's shared API client. Browser-managed image/static-asset loads, Stripe-hosted pages and legacy Blade AJAX are outside this tracker.

## Adding a data region

Requests through `workspaceApi`/`client` are tracked automatically. Keep the contextual loader mounted and pass an explicit boolean so the common timing policy can complete:

```jsx
import LoadingState from '../components/LoadingState';

<LoadingState
  busy={Boolean(state.loading)}
  label="Loading products…"
  variant={state.result ? 'inline' : 'table'}
  columns={5}
/>
```

Set `aria-busy={Boolean(state.loading)}` on the affected data region. Keep the live loading message outside that busy region; decorative skeletons are hidden from assistive technology. Clear loading in success/error paths, preserve prior records on refresh, and retain existing stale-response guards.

For background work that should remain silent:

```js
await workspaceApi.get('workspace/alerts', { globalLoader: false });
// This flag controls loading feedback only; notifications are configured separately.
```

For asynchronous work that does not use Axios:

```js
import { loadingActivity } from '../loading/activity';

const finish = loadingActivity.begin();
try {
  await doWork();
} finally {
  finish();
}
```

Do not reset a global boolean on an individual request's completion: other requests may still be running. Do not add automatic financial retries, arbitrary request timeouts or global blocking overlays as part of loading feedback.

## Verification

- **156 frontend tests passed across 13 files**, including 17 new loading tests.
- Production build passed. JavaScript output is approximately 472KB / 143KB gzip; no loader library was added.
- New tests cover concurrent requests, delayed appearance, minimum visibility, success/failure/network exceptions, abort/pre-abort, configuration reuse, CSRF/explicit opt-outs, focus/navigation, nested dialogs, all contextual variants and retained rows after a failed refresh.
- Headless Chrome uses an isolated fixture with deliberately held read responses. It checks initial skeletons, light/dark/mobile display, reduced motion, retained rows and `aria-busy` on refresh, partial completion of concurrent modal requests, accessible portal placement, and pending activity surviving dialog closure. The same run exercises existing theme, sidebar, validation, CRUD, notifications and legacy icon regressions. No POS database access or real Laravel writes occur.
- [Browser evidence](audit/global-loading-browser.json) and screenshots: [light list](design/loading/light-list.png), [dark list](design/loading/dark-list.png), [mobile](design/loading/dark-mobile.png), [refresh](design/loading/list-refresh.png), [dialog](design/loading/modal-options.png).

Manual acceptance: throttle the browser network, open Products and a setup/list page, search/page while checking that prior rows remain, open Add product while references load, cancel the dialog, simulate a failed request and retry, and repeat in dark mode with keyboard/reduced-motion enabled. Fast successful requests should finish without a flash; no failed request should leave an endless indicator.

Accessibility references: [WAI indeterminate progress semantics](https://www.w3.org/WAI/ARIA/apg/practices/range-related-properties/#range-properties-with-progress-bars), [progressbar and busy-region guidance](https://developer.mozilla.org/en-US/docs/Web/Accessibility/ARIA/Reference/Roles/progressbar_role). API integration follows the shared Axios [interceptor mechanism](https://axios-http.com/docs/interceptors).
