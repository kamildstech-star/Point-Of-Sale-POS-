# UX-3 — Cashier usability

Implemented after the user approved UX-2 and authorized UX-3. This follows §7 and §10 of the [refinement plan](UNIVERSAL_POS_UI_UX_REFINEMENT_IMPLEMENTATION_PLAN.md), retaining the established comfortable selling-desk density.

## Audit and changes

| Existing behavior | UX-3 refinement | Compatibility and verification |
| --- | --- | --- |
| Product search has scanner Enter handling but no entry-focus policy or discoverable focus action | Safe desktop initial focus, visible Focus product search button and F2 hint | No automatic touch keyboard. Existing controls, accessibility navigation and dialogs retain focus. F2 is ignored inside editable controls, with modifiers, during locks and inside dialogs. No payment shortcut was added |
| Product lookup has no localized loading/no-match presentation; a rapid scan can overlap a request | Local searching/scanning status, explicit no-match/ambiguous-scan text, synchronous duplicate-scan guard and late-response protection | Same lookup API and exact-SKU/barcode Enter workflow. A response cannot add an item after the POS editor locks. Product selection waits for refreshed results |
| Customer, pricing, totals and payment actions scroll away | Sticky desktop payment panel with its own scroll region and a persistent amount-to-collect/Complete sale footer | Available height responds to the real panel position, window size and content. Exact server amounts stay beside the action; fields remain reachable in a long panel |
| Stacked mobile layout makes payment difficult to find after a long cart | Fixed mobile amount/status bar with Review payment anchor | The anchor moves focus to Payment summary. For an unconfirmed submission it becomes Review checkout and focuses the persistent outcome notice. Confirmed sales expose the actual receipt link |
| Quote expiry is only a timestamp | Ready/expired quote notice with business timezone and Refresh quote action | A timer updates display state without server polling. Expired quotes block new checkout and payment editing. Laravel still performs authoritative expiry, price, tax and stock validation |
| Quote calculation and checkout share ambiguous busy text | Separate calculation, confirmation, rejected, unconfirmed and completed presentation | Existing request keys, financial inputs, payment validation, known rejection statuses and duplicate-submit guard are retained |
| Completion only exposes the receipt | Explicit Start new sale action after confirmed completion | Clears the editor and customer/pricing selections, retains the shift, focuses search, and removes completed exchange/recall URL flags. It does not automatically create another sale |

F2 was selected after inspecting existing application keyboard handlers; no existing F2 binding was present. It only focuses search. Existing Enter scanning remains in the scanner input. Physical scanner hardware has not been tested in this phase.

## Transaction guarantees preserved

- Quotes still submit variant IDs and decimal quantity strings, customer, price-list, discount and shift selections to the existing Laravel API. React does not recalculate tax, totals, payment due or exchange credit.
- Checkout still captures one request body containing the original idempotency key, quote ID and confirmed payment details. Rapid repeat clicks are locked.
- A network/server failure with an uncertain outcome keeps the editor locked. Retry sends the identical original request. No new quote, key, payment payload or automatic checkout is generated for that retry.
- An unconfirmed retry remains available even when its original quote subsequently expires. The Laravel idempotency service remains authoritative for resolving that request.
- Existing 401/403/409/419/422 rejection handling is retained. A conflict invalidates the quote and requires a fresh quote.
- Hold/recall remains the existing versioned workflow. Held carts preserve selections and quantities; their existing notice explains that prices/stock are checked again and payments/exchange credits are not saved.
- Confirmed completion provides a real `/sales/{id}` receipt link. Exchange completion continues to use its existing exchange endpoint; the new-sale action leaves that completed exchange context before another quote.

No Laravel route, permission grant, database schema, financial calculation or stock-posting rule changed. No dependency was added. Existing named-customer credit, walk-in full-payment policy and external payment confirmation fields remain in use.

## Source map

Paths below are relative to `POS Frontend/src/`.

| Files | Purpose |
| --- | --- |
| `features/operations/PosPage.jsx` | Checkout composition, current shift context, persistent amount/action, explicit outcome states, receipt/new-sale actions and context reset |
| `features/operations/posUsability.js` | Safe focus/shortcut policy, quote expiry lifecycle, visible panel measurement and cleanup |
| `features/operations/PosQuoteStatus.jsx` | Quote readiness, expiration and explicit refresh action |
| `features/operations/OperationUI.jsx` | Shared lookup loading, scan guard, stale-response handling and scan-focus restoration |
| `styles/pos.css`, `main.jsx` | Scoped selling-desk layout, desktop sticky panel/footer, mobile summary and semantic theme styling |
| `app/App.jsx` | Load the selling desk on demand with the existing shared loading component |
| `test/PosUsability.test.jsx` | Focus, expiry, exact footer amount, safe retry, context reset and scan-race coverage |
| `test/HeldCartsFlow.test.jsx` | Use a current valid quote fixture for the preserved hold/recall lifecycle |
| `test/DashboardShell.test.jsx` | Allow the initial session/context view to resolve on slower Windows runs while preserving every permission assertion |

The shared ProductFinder changes also apply to the existing stock/purchase line editor. Its search request shape, explicit selection and lack of autofocus are retained. These consumers are covered by the existing regression suite.

## Review evidence

Open the [UX-3 screenshot gallery](design/refinement/ux3/index.html). Four before screenshots were captured from the verified UX-2 build before building UX-3. Current screenshots cover both themes, desktop/mobile, long carts, payment anchoring, expired quotes, rejected/unconfirmed checkout and confirmed completion.

The [verification summary](audit/refinement-ux3-verification.json) records the React reports, browser checks and final production assets. Browser reports are separate from UX-1/UX-2 historical evidence.

Verification results:

- **245 React tests passed across 22 files**. All existing workflows remain in the suite; 16 cashier usability cases were added.
- **33 focused POS tests passed across three files**, covering the final scan reset, exact server amounts, quote expiry, duplicate-submit protection, split payments, exchange context, safe retry and held carts.
- **46 cashier usability browser checks passed**, producing **22 screenshots**. These cover safe focus/F2, scanner Enter, desktop sticky panel/action, mobile anchoring, exact values, expiration/refresh, rejection, unconfirmed retry after expiry and confirmed receipt/new-sale behavior.
- **36 cashier/manager browser regression checks passed**, including hold/recall, fresh quotes, completed checkout, receipt links, no-shift/no-assignment states, loading, retry and revocation.
- Combined browser evidence: **82 checks, 43 current screenshots and four baseline screenshots**. Browser reports were refreshed after the final build.
- **Production build passed**. Main JavaScript is approximately **487.59kB** before gzip; the selling desk loads as a separate approximately **16.15kB** chunk. No dependencies were added.
- Chrome evidence uses zero writes to the working database. Both themes, 360/768/1280/1440/1920 widths, 125%/200% effective reflow, text contrast, touch targets, keyboard focus and long-cart behavior are covered.

From `POS Frontend`:

```powershell
npm.cmd test -- --testTimeout=20000
npm.cmd run build
node scripts/refinement-ux3-browser-smoke.mjs
node scripts/refinement-ux3-staff-browser-smoke.mjs
node scripts/record-ux3-verification.mjs
```

The Chrome previews run against synthetic HTTP fixtures and write nothing to the working POS database. They verify UI behavior and request consistency, not a fresh live-backend isolation audit or physical scanner operation. The local clock drives the expiry presentation; server validation remains authoritative.

## Quick manual review

1. Open Point of sale with an authorized open shift. Check the displayed branch/register/shift context. Use Focus product search or F2 outside an input, then scan/type an exact SKU and press Enter.
2. Add several product lines and request a quote. Scroll a long cart on desktop: the payment panel, quoted amount to collect and Complete sale action should remain reachable together.
3. At a narrow width, use Review payment. The Payment summary heading receives focus; the bottom bar retains the server amount. Input values remain intact while scrolling.
4. Review expiry, rejected/unconfirmed checkout and completion in the screenshot gallery or synthetic browser harness. These scenarios have automated request/body checks and do not require changing working POS records.
5. In a test business, confirm a sale and open its actual receipt. Start new sale should clear customer/pricing/cart selections and retain the shift. After an exchange it should leave the completed exchange context.

## Review boundary

The supplied plan §10 says to implement phases individually, “requesting review at the end of each phase.” The user approved UX-3 and authorized [UX-4 role composition](UX4_REFINEMENT.md). Its implementation and verification are recorded separately; optional UX-5 enhancements remain a subsequent review point.
