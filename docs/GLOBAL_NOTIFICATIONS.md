# Global toast notifications

React Toastify 11.1 is installed in the React JavaScript frontend. One global container displays notifications at the top right, including on small screens. Setup follows the library's [official installation guide](https://fkhadra.github.io/react-toastify/installation/).

Toast status and close icons now use the shared [Remix Icon integration](REMIX_ICONS.md). Notification behavior and timing remain as described below.

## Behavior

- Successful API actions show a message describing the outcome: created/updated records, archived products, posted stock/purchases, completed sales, recorded payments/refunds, shifts, expenses and workspace tools.
- Sign-in, sign-out, business creation, registration and business switches are announced after authentication state is updated.
- Failed API requests show error toasts. The existing form summary remains above the fields, with persistent field-level validation messages and retained input values.
- Invalid client submissions show a validation toast without sending a save request. Correcting and submitting the form dismisses that old toast. A successful retry also dismisses earlier API errors for that action.
- Ordinary list loads and polling stay silent. Running a report explicitly announces completion.
- Invalid import previews and reconciliation issues show warnings. Valid import previews ask the user to review/post the import. Queued exports are described as requested; ready exports are described as ready to download.
- Stripe Checkout and Portal actions show informational redirect messages. They do not claim that payment succeeded or subscription access was granted.
- Expected unauthenticated startup checks and canceled requests stay silent. Session expiry clears the previous notifications, shows one sign-in message, and clears the authenticated view.
- Repeated reports of the same failure are deduplicated across API interceptors, form catches and inline notices. At most three toasts are displayed at once; identical error bursts are suppressed.
- Toasts pause on hover or lost window focus. Success/info/warning messages normally close after 4.5 seconds; errors after 7 seconds. A close button remains available. Notifications are excluded from printed receipts.

The toast container moves into the active popup without remounting. This keeps its announcements and close buttons accessible inside the modal's focus boundary. When the popup closes, the same container returns to the document body and its current messages remain available. Nested dialogs use the topmost popup.

Notifications are cleared on authentication/business changes. In-flight responses from a previous notification scope cannot create new toasts in the next business/session. No request payloads or credentials are included in toast IDs or logged by the notification layer.

## Implementation and reuse

- `src/components/GlobalToaster.jsx` mounts once in `main.jsx`.
- `src/notifications/index.js` exports `notify.success`, `notify.error`, `notify.warning`, `notify.info`, and notification lifecycle helpers.
- `src/notifications/apiMessages.js` maps API actions to user-facing outcomes. Add specific wording here for future API actions rather than copying notification calls into every form.
- Axios interceptors in `src/api/client.js` announce API outcomes; `SaveForm` handles client-side validation. Avoid adding another success toast to an action already handled by the interceptor.
- `src/notifications/host.js` and `Modal.jsx` keep the single portal inside the active dialog when needed.
- `src/styles/notifications.css` provides sizing, brand colors, close-button styles, reduced-motion handling and print hiding.

For a local UI event that has no API request:

```javascript
import { notify } from '../notifications';

notify.info('Choose a stock location to continue.');
```

For an explicit read action, such as running a report:

```javascript
await workspaceApi.get('reports/summary?from=2026-10-01&to=2026-10-23', {
  notifications: { success: 'Report generated successfully.' },
});
```

An Axios request can opt out with `notifications: false`, or provide a custom `notifications.success` message. Returned promises and HTTP errors retain their existing behavior; the notification layer does not turn failed requests into successful results.

## Verification

The [verification record](audit/global-notifications-verification.md) contains the final test counts and browser results.

`src/test/Notifications.test.jsx` exercises saved records, read-request silence, local validation, server validation deduplication, HTTP/network errors, canceled/guest requests, import warnings, Stripe messaging, export readiness, successful retries, stale responses, nested modal accessibility, session expiry, authentication and burst deduplication.

The existing validation, modal, POS, finance, catalog and authentication tests remain part of the regression suite. Browser checks use a local fixture serving the production build; they do not write to the working Laravel database. Private evidence includes `toast-product-success-desktop.png`, `toast-category-error-mobile.png` and `modal-browser-verification.json` under `docs/audit/private/`.

Run checks from `POS Frontend`:

```powershell
npm.cmd test -- --testTimeout=15000
npm.cmd run build
```

No Laravel migrations, seeders, credentials or existing POS records are changed by this feature.
