# Dashboard D4 — Cashier and Branch Manager

D4 is implemented at the existing React **Overview** route (`/`). The user's request authorizes D4 from the [supplied specification](UNIVERSAL_POS_ROLE_BASED_DASHBOARD_UI_UX_SPEC.md); its generic embedded prompt does not authorize implementing D5–D7. Owner/admin dashboards and existing operational routes remain available.

## Cashier dashboard

- A large **New Sale** action opens POS with the selected personal shift. With no open shift, **Open register shift** opens the actual register form.
- Current register/branch, opening time, own open shifts, own shift sales, own shift invoice count and own held carts.
- Own recent receipts, server-paginated five per page, with working receipt and authorized return links. Receipt dates default to today in the business timezone.
- Personal shift review/closing, product lookup and customer lookup shortcuts.
- **Hold / Recall** opens the actual saved-cart workflow in POS.

`dashboard/cashier-summary` always narrows to the actor's own shifts and invoices, including when an additional permission grants reports or cash management. The cashier page does not request company summaries, profit, staff activity or branch comparison. Existing permission-based navigation is unchanged: an explicitly granted extra role still has its authorized modules.

Shift sales include tax and subtract lifetime posted credits against the actor's invoices in the selected open shift. They are not cash collections and do not represent everybody's invoices in a shared shift. Original invoice counts include later-voided invoices. No open shift produces **Unavailable** shift figures, not invented zero sales. Recent receipts use the displayed dates; shift KPIs cover the lifetime of the selected open shift. The shift selector lists the latest 20 own sessions, includes an explicitly selected permitted session, and links to the paginated Register shifts module for the full list.

## Branch Manager dashboard

- One permitted branch at a time, defaulting to the first allowed branch; no tenant-wide "All branches" selection in this profile.
- Net sales excluding tax, original invoice count, average original invoice value including tax, and posted return/cancellation credits.
- Daily branch sales, scoped stock alerts, current approvals, recent branch receipts and recorded staff activity.
- Today, last seven days, last 30 days and validated custom periods; branch/date changes reload dependent widgets and reset list pages/shift filters.
- Staff rows open an actual staff/branch/date-filtered sales list. Approval links open scoped source documents. Branch shortcuts retain their filters.

Staff activity means employees with invoices/posted credits in the period or a current permitted open shift. It is not attendance or an assigned-employee headcount. Names and totals require both `team.view` and `reports.view`, and an explicit personal-sales restriction disables the endpoint. Other staff's current shift counts additionally require `cash.open` and `cash.manage`; otherwise they are unavailable. Staff activity uses the same invoice/posted-credit and decimal tax rules as D2 reports.

**Branch policy:** the specification proposes assigned-branch managers. The previously approved user policy preserves existing staff access and applies explicit restrictions. D4 therefore does not silently change memberships, seed grants or assign/restrict every existing manager. A manager with an explicit assignment policy sees only those allowed branches; no assignments means no dashboard branch data or widget requests. Existing unrestricted managers can choose among their currently permitted branches. Laravel validates every selected branch and applies the established D2 record scope.

## Hold / Recall workflow

Held carts were absent in D0–D3. D4 adds a real workflow rather than an illustrative count:

1. Add products and select a valid open register shift in POS.
2. Choose **Hold cart**, enter an optional label in the popup and save. Quantities, customer/price-list IDs and discount are saved; the editor clears only after confirmation.
3. **Recall held cart** opens the actor's server-paginated saved-cart list. Recall is blocked while an unsaved cart is present; hold or explicitly clear that editor first.
4. Recall restores selections and quantities, clears quotes/payments, and selects a permitted open shift in the original branch when one is available. A closed original shift does not delete the saved cart.
5. Request a new server quote. Current product status, prices, tax, discount permission, unit precision, stock and register policy are revalidated by the normal POS flow.
6. Checkout atomically consumes the held cart along with the sale. Version checks prevent two linked tabs from selling the same held record. Checkout idempotency remains intact.
7. Explicitly discarding a held cart requires confirmation and retains its history. Clearing the editor does not discard the saved record.

Saved carts are private to their creator, even for managers/owners. They do not reserve stock, create invoices, record payments or preserve exchange credits. A return/exchange cart cannot be held. Saving supports idempotent creation/update; an unconfirmed result locks checkout and retries the identical request/key. Stale versions, revoked branch access and cross-tenant/actor IDs are refused. There is a limit of 50 active visible held carts per staff member; each cart has at most 100 distinct lines. No automatic expiry, deletion, charging or cross-staff transfer is introduced.

## API changes

All routes retain session/CSRF authentication, server-resolved tenant context, effective permissions, subscription policy and private response handling.

| Endpoint | Purpose |
| --- | --- |
| `GET /api/v1/dashboard/cashier-summary` | Always-personal shift summary, held count and paginated safe receipt headers |
| `GET /api/v1/dashboard/staff-activity` | Permitted branch's paginated activity; requires a selected branch and report/team permissions |
| `GET /api/v1/pos/held-carts` | Own active held carts, ten per page; optional permitted branch filter |
| `POST /api/v1/pos/held-carts` | Idempotently hold/update an own cart; requires `sales.create` |
| `GET /api/v1/pos/held-carts/{id}` | Recallable own cart data; no cost fields or saved payment details |
| `POST /api/v1/pos/held-carts/{id}/discard` | Version-checked, idempotent own-cart discard |

The existing quote endpoint accepts a paired `held_cart_id` / `held_cart_version`; checkout consumes it under the existing tenant transaction lock. Dashboard cache keys include the selected shift and the updated contract version. Context resolves presentation profiles in this order: owner, admin, manager, cashier. Presentation does not grant widget permissions.

Existing Sales consumes branch/date/personal/shift filters; selecting another cashier additionally requires report/team access and cannot bypass an explicit own-sales policy. Existing receipt detail accepts a narrowing `own=1`. Register lists/reconciliation consume personal/branch/document filters, and register options consume a permitted branch filter. Filtered lists disclose their filters, reset pagination appropriately and retain server pagination. Optional widgets retain inline retry; authorization failures clear old figures. Errors remain above forms.

## Database, rollout and rollback

The additive `2026_11_03_170000_create_pos_held_carts` migration creates one empty table with composite tenant foreign keys, an actor/status/branch listing index and version/history fields. It does not alter existing POS tables or grants. It was previewed and applied to the local database. Before/after read-only fingerprints confirmed all **103 original business tables**, existing role grants and the backend `.env` were unchanged; no platform grants or explicit access policies were created. See [migration evidence](audit/dashboard-d4-migration.json).

Other installations must apply that migration with their normal deployment process. Before migration, the cashier dashboard can still load and held-cart capability is unavailable. Do not run seeders to enable these dashboards.

`VITE_STAFF_DASHBOARD_ENABLED=false` restores the welcome overview for manager/cashier profiles after restart/rebuild. The existing D3 overview rollback flag also remains in effect. Keep D2 access enforcement and saved-cart history when rolling back UI code; destructive migration rollback is refused. Saved-cart controls in POS are enabled by the actual schema and sales permission, independently of whether the new staff overview is displayed.

Dashboard bodies and saved-cart controls are separate production chunks. The implementation reuses React JSX, existing native/shadcn-style primitives, Remix icons, one theme provider, regional/global loaders, Toastify, top-of-form validation and accessible modals. No package or icon library was added. Live Stripe charging is unchanged; no staging server is available.

## Verification and manual acceptance

Backend coverage includes personal cashier metrics despite broad additional grants, branch-restricted managers, missing assignments, staff financial/shift permission separation, tenant/actor isolation, composite foreign keys, validation, idempotent holds, stale versions, closed-shift recall, fresh prices and duplicate-checkout prevention. Existing POS, returns, catalog and billing tests remain included.

React coverage includes both profiles, data request/permission selection, filters, actual receipt/register/source links, empty states, popup validation, protected cart replacement, recall selection restoration, fresh quoting, held checkout, unconfirmed-result retries and explicit discard/clear actions.

Actual Chrome checks cover both themes at 360, 768, 1280 and 1440 pixels, AA text contrast, the cashier touch target, private receipt links, real popup Hold/Recall/quote/checkout, opening a shift, no branch assignments, partial retry, revoked access and named loaders. [Preview screenshots](design/d4/) contain synthetic fixtures only. Those deliberately include extra report/cash permissions to verify the cashier view still requests personal data; production navigation follows actual grants. No test fixture is imported into the application.

Final results are recorded in [verification evidence](audit/dashboard-d4-verification.json), [browser evidence](audit/dashboard-d4-browser.json) and [real-driver read-only SQL evidence](audit/dashboard-d4-sql-smoke.json).

Verified November 3, 2026: **133 Laravel tests / 2,633 assertions**, **208 React tests across 19 files**, production build, **36 Chrome checks / 21 screenshots**, PHP formatting across 15 files, and **13 real MariaDB read-only dashboard operations**. Final browser screenshots were visually inspected. Before/after migration fingerprints preserve all 103 existing business tables and the backend environment/grants. No new working sales, payments, inventory movements, demo users or subscriptions were seeded by this implementation.

Manual UAT:

1. Sign in with a cashier membership. Open Overview, select an own shift and use New Sale. Check that POS preselects that shift.
2. With no own open shift, use Open register shift and complete the popup. Return to Overview and refresh.
3. Post a sale, partial return and cancellation in a test business. Compare own shift figures with the original invoice/credit records; another cashier's sale must not appear in the personal dashboard.
4. Hold a labeled cart, recall it, change current catalog price and request a fresh quote. Confirm current pricing is used and stock changes only when checkout posts.
5. Load one held cart in two tabs, quote both and complete the first. The other linked checkout must be refused. Retry the first request with its original key and verify one sale.
6. Hold again, close its source shift, open another shift in the same branch and recall. Another branch must be refused for checkout.
7. Test an invalid quantity, inactive product, missing shift, unavailable stock and revoked discount permission. Confirm clear top-of-form errors without losing the cart.
8. Simulate an unconfirmed hold response; retry with unchanged payload/key. Confirm checkout stays locked until acknowledged.
9. Attempt Recall over an unsaved editor, clear that editor with confirmation, and separately discard a saved cart. Confirm only explicit discard removes it from the active list.
10. Sign in as a manager; select a permitted branch and period. Follow a staff row, receipt, stock alert and approval and verify the selected source filters.
11. Restrict a test manager to one branch, then no branches, then personal sales. Test forged URLs through the API as well as the UI. Do not change production grants merely to run UAT.
12. Test report/team access without cash management: staff sales can be authorized while other staff shift counts stay unavailable.
13. Test both themes, all four widths, keyboard-only modal navigation, pagination, refresh, empty lists, request errors and role/business switching.

D5–D7 dashboards and the original staging/release gates remain pending.
