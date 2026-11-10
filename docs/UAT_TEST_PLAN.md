# Complete manual test cases and acceptance scenarios

Prepared: **2026-10-20 (Asia/Karachi)**. Scope: the current React JavaScript / Laravel POS implementation through Phase 7, plus Phase 8 acceptance targets. This pack contains **257 cases across 20 areas**.

**These manual cases have not been executed by creating this document.** Every row in [UAT_TEST_CASES.csv](UAT_TEST_CASES.csv) starts as **Not Run**. The previously recorded automated baseline is 67 backend tests / 1,011 assertions, 25 React tests and a passing production frontend build; it is not a claim that all these additional cases have passed. See [the verification record](audit/phase-6-7-verification.md).

Use the Markdown guide for instructions and the CSV for recording Status, ActualResult, Evidence, DefectID, Tester and TestDate. A scenario may require several negative input variations; record which variations were exercised.

Current UI: Add/Edit/action buttons open forms in popup modals. Open the relevant dialog before entering the fields in these cases; see [popup behavior and verification](POPUP_FORMS.md). Existing manual results remain unchanged.

Edit/delete update: use the pencil/trash icons for editable records. Additional acceptance scenarios, archive/restore behavior and protected history are described in [RECORD_ACTIONS.md](RECORD_ACTIONS.md).

Validation update: forms now show JavaScript and Laravel errors beside fields. Test the invalid-input variations in [FORM_VALIDATION.md](FORM_VALIDATION.md), including direct API submissions that bypass the client. This update does not mark any manual case as passed.

Dashboard D5–D7 update (2026-11-05): specialist activity, mandatory personal associate sales, an aggregate-only platform workspace and pilot controls are documented in [DASHBOARD_D5_D6_D7.md](DASHBOARD_D5_D6_D7.md). Additional D567 cases in the CSV supplement the original 257-case pack. Automated evidence is separate from manual UAT; complete [the role sign-off matrix](DASHBOARD_ROLE_SIGNOFF.md) and [pilot feedback](DASHBOARD_PILOT_FEEDBACK.csv) before widening rollout. Staging/device acceptance remains pending.

## 1. Test environment and prerequisites

1. Use a **disposable UAT copy restored into a separately named database**, with matching backend/frontend configuration. Browser tests create real records in that copy. Do not test cancellation, stock changes, suspension, corrupt backups or cutover against the only working/source database.
2. Preserve the existing application key and recovery material. Do not run the historical DatabaseSeeder, migrate:fresh or migrate:refresh as a shortcut to preparing existing business data. Test-only fixtures and independently verified restores are the supported basis.
3. Start Laravel and React in separate terminals. The following are development commands, not production hosting:

   ```powershell
   # From the project root, terminal 1
   Set-Location 'POS backend'
   php artisan serve --host=127.0.0.1 --port=8000
   ```

   ```powershell
   # From the project root, terminal 2
   Set-Location 'POS Frontend'
   npm.cmd run dev -- --port 3002
   ```

   Open http://127.0.0.1:3002. Use the configured API target and matching FRONTEND_URL for Stripe returns. The development proxy forwards API/session requests. HTTPS-specific cases require the staging configuration; they cannot be proven by HTTP localhost.
4. Have owner, admin, manager, cashier, inventory clerk, accountant and viewer test identities, plus an A-only, B-only and shared-business user. Use separate browser profiles for simultaneous sessions. Check the actual granted permission codes; existing/custom grants may differ from defaults.
5. Have separate business A/B datasets. **A newly created business has no verified paid access by default.** For posting tests use a preserved-access legacy business in the UAT restore or a business with a verified Stripe test subscription. Without an eligible state, creation/posting being blocked is expected. Mark dependent cases Blocked rather than bypassing access through SQL or a success URL.
6. Define the physical store, register, currency, units, prices, tax configuration and customer fixtures below. Branch activation must be reviewed before checkout; it freezes applicable legacy writers in that UAT business. No branch was activated automatically by installation.
7. Stripe cases require real **test-mode** credentials and actual configured test Price IDs, signature configuration, mapped Customer Portal prices, queue worker and appropriate test-account tooling. Follow [STRIPE_BILLING.md](STRIPE_BILLING.md). Live charging is not approved. Never use live keys/payment instruments in these tests.
8. Use fresh independent fixtures for numerical cases unless a case explicitly says to continue the previous fixture. Add a case suffix to fixture SKUs/names, or restore the disposable checkpoint. Do not run every case consecutively against one changing stock balance and expect all isolated numbers still to match.
9. Actual scanner/printer, external Stripe and staging/off-host cases require those resources. Missing resources mean **Blocked**, not Pass. Developer fixture/API/race cases are not all achievable by clicking the UI.

## 2. Fixture data

These are proposed **test-only inputs**, not records automatically seeded or posted by this pack. Enter no real customer information or production secrets in result files.

| Fixture | Values / purpose |
| --- | --- |
| Business A | UAT-A, PKR, Asia/Karachi; eligible access for retail mutation tests |
| Business B | UAT-B, separate members/data; eligible access before testing its postings |
| Branch A1 | Active, reviewed React POS activation, TEST tax jurisdiction, named-customer credit enabled |
| Branch A2 | Separate active test branch, named-customer credit disabled |
| Store / register | Store belonging to the selected branch; active register assigned to that store |
| EA | Quantity precision 0 |
| KG | Quantity precision 3 |
| SHIRT-100 | Simple physical SKU; EA; price 100.00; cost 60.00; no tax; on-hand 20 for standard sale/inventory cases |
| TAXED-100 | Physical SKU; price 100.00; cost 60.00; active class/rate 10% in TEST jurisdiction on the test date |
| CENT-123456 | Physical EA SKU; price 12.3456; no tax; enough stock for two one-unit returns |
| FABRIC-200 | Physical KG SKU; price 200.00 per KG; cost 120.00; on-hand 5.000 |
| SERVICE-50 | Service SKU; price 50.00; no physical inventory tracking |
| DIGITAL-75 | Digital SKU; price 75.00; no physical inventory tracking |
| Variable shirt | Separate S/M variants with unique SKUs; Size attribute and valid values |
| Barcode | Unique Code 128 test text such as UAT100001, mapped to the intended SKU |
| Customer ALICE | Named fictional retail buyer; test-only contact details |
| Supplier SUPPLIER-A | Fictional supplier with reviewed test payment terms |
| Payment methods | Cash; external card terminal; confirmed bank transfer |
| Expense category | UAT-Supplies |
| External references | Fake confirmed-terminal/bank references, e.g. UAT-TERM-001; this app records retail payments rather than charging the terminal |
| Purchase golden fixture | Fresh SKU at zero stock; PO 20 units x 60.00; zero purchase tax |
| Cash golden fixture | New shift opening 1000.00; exact activities listed below |
| Stripe plans | Reviewed provisional/configurable terms; actual matching test Price IDs only; never invent IDs |

A no-tax 2 x SHIRT-100 sale is **200.00**. A discount/tax golden sale is **200.00 subtotal - 20.00 discount + 18.00 tax = 198.00**. Fabric quantity 0.125 x 200.00 is **25.00**.

## 3. Execution method, priority and result rules

| Label | Meaning |
| --- | --- |
| Browser | Manual interaction with React and authorized test records |
| API | Controlled HTTP testing against the UAT API, retaining session/CSRF where required; use [the API contracts](api-contracts/phase-6-7.md) |
| Fixture | Developer-controlled isolated test setup / fake gateway / clock travel; never change the host production clock or forge production payment evidence |
| Stripe | Real sandbox/test-account integration; automatic SDK/signature tests alone do not prove this |
| Race | Synchronized separate processes or sessions on disposable MariaDB; sequential retries are insufficient |
| Hardware | Actual scanner/printer acceptance |
| CLI / Ops / Review | Trusted operator commands, recovery/deployment rehearsal or documented business acceptance |
| P0 | Critical money, stock, access/isolation, preservation or launch gate |
| P1 | Core workflow/validation acceptance |
| P2 | Usability/layout/copy acceptance |

Allowed result statuses: **Not Run**, **Pass**, **Fail**, **Blocked**, **Not Applicable**. Explain Blocked and Not Applicable; do not use them to hide a critical production requirement. Mark Pass only after checking the expected outcome and retained evidence. Link every Fail to a defect. Clear user-visible rejection is expected for negative tests; a generic unexpected 500 is not a passing validation outcome.

Capture before/after business, branch, register, SKU/stock, invoice/return IDs, amounts/statuses, request ID, browser result and relevant authorized operator evidence. Retain evidence privately; do not include passwords, keys, card details, session cookies or customer PII in a committed CSV.

## 4. First-pass smoke sequence

The full cases follow; start with these paths before the deeper negative/concurrency checks.

| Order | Cases | What to prove |
| --- | --- | --- |
| 1 | AUTH-01, AUTH-06 | Login, session persistence and logout |
| 2 | CAT-01, SET-01, SET-02, SET-04, SET-05 | Product, locations, register and reviewed activation |
| 3 | PUR-02 to PUR-05 | Draft/approve PO and receive stock without double counting |
| 4 | CASH-01, POS-04 | Open shift, make a full cash sale, verify one stock deduction |
| 5 | PAY-03, PAY-07 | Named credit sale and later collections |
| 6 | RET-01, RET-06, RET-11, RET-17 | Posted return, damaged goods, refund and linked exchange |
| 7 | EXP-02, EXP-03, CASH-06, CASH-07 | Expenses, cash reconciliation and closing variance |
| 8 | REP-01, TEN-03, TEN-04, ROLE-05 | Financial report and access boundaries |
| 9 | TOOL-08, TOOL-12, TOOL-13 | CSV validation and private scoped downloads |
| 10 | BILL-02, BILL-12, BILL-14, BILL-18 | Billing setup guard, verified activation, duplicate event and grace; real-account steps stay Blocked until configured |
| 11 | OPS-01, OPS-02, OPS-07 | Automated regression/build and isolated encrypted restore |
| 12 | CUT-01 to CUT-08 | Pending Phase 8 acceptance; do not call the project production-ready from localhost smoke tests |

## 5. Exact financial checkpoints

### Tax, discount and returns

| Operation | Expected |
| --- | --- |
| Sell 2 units at 100; discount 20 before 10% tax | Subtotal 200.00; discount 20.00; tax 18.00; total 198.00 |
| Return both units using original values | Total credit 198.00; returned tax 18.00; returned discount 20.00 |
| Sell 2 units at 12.3456, no tax | Invoice total 24.69 |
| Return those two units separately | First credit 12.35; second 12.34; cumulative 24.69 |

### Credit-before-refund

| Step | Remaining invoice value | Balance due | Refund due |
| --- | ---: | ---: | ---: |
| Sell 200.00 to named customer and collect 50.00 | 200.00 | 150.00 | 0.00 |
| Post first 100.00 return | 100.00 | 50.00 | 0.00 |
| Post second 100.00 return | 0.00 | 0.00 | 50.00 |
| Refund collected 50.00 | 0.00 | 0.00 | 0.00 |

A return does not imply all invoice value was collected. Refunds cannot exceed eligible collected money.

### Cash closing

| Component | Amount |
| --- | ---: |
| Opening cash | 1000.00 |
| Cash collected | +200.00 |
| Cash refunded | -100.00 |
| Cash in | +50.00 |
| Cash out | -20.00 |
| Approved cash expense | -30.00 |
| Expected drawer cash | **1100.00** |
| Actual counted cash | **1095.00** |
| Variance: counted minus expected | **-5.00** |

Card/bank entries and internal exchange credit do not add physical drawer cash. This is a separate independent fixture from other sales tests.

### Purchase receipts

PO: 20 units x 60.00 = 1200.00, starting at zero stock. Receipt: 8 accepted and 2 rejected -> on-hand 8, receipt payable 480.00, accepted quantity still outstanding 12. Returning 2 accepted units reduces stock to 6 and that original obligation by 120.00. If 100.00 was already settled, remaining payable is 260.00. A full settlement followed by a valid supplier return retains a supplier credit instead of deleting payment history.

## 6. Full test case catalog

Each module has shared prerequisites, followed by explicit actions and expected outcomes. The CSV contains the same IDs and text for execution tracking.

| Code | Area | Cases |
| --- | --- | ---: |
| AUTH | Login, registration and sessions | 12 |
| TEN | Businesses and tenant isolation | 12 |
| ROLE | Staff, roles and authorization | 10 |
| CAT | Products, variants and catalog setup | 18 |
| SET | Branches, registers and POS activation | 10 |
| LEG | Legacy mapping and source preservation | 10 |
| INV | Stock, adjustments, counts and reservations | 14 |
| PUR | Suppliers, purchasing and payables | 14 |
| POS | Point of sale, quotes and checkout | 18 |
| PAY | Customers, collections, invoices and receipts | 13 |
| RET | Retail returns, cancellation, refunds and exchanges | 19 |
| CASH | Register shifts and cash reconciliation | 11 |
| EXP | Expense categories, drafts and approvals | 8 |
| REP | Financial reports and reconciliation | 10 |
| BILL | Stripe SaaS subscriptions and entitlements | 26 |
| TOOL | Workspace settings, alerts, audit, CSV and support | 16 |
| UX | Navigation, browser behavior and hardware acceptance | 10 |
| RACE | Concurrent requests and network retries | 8 |
| OPS | Automated regression, monitoring and recovery | 10 |
| CUT | Phase 8 populated-data rehearsal and launch acceptance | 8 |

### AUTH — Login, registration and sessions

Prerequisites: Use existing test staff identities, one legacy customer-only identity, and a separate browser profile without a session.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| AUTH-01 | P0 / Browser | Valid staff login | Sign in with the correct test staff email/password; refresh the page. | Login succeeds, refresh retains the session, and only permitted business data is visible. |
| AUTH-02 | P1 / Browser | Invalid credentials | Enter an incorrect password; repeat with an unknown email. | Login is rejected with a safe message; no authenticated session is created. |
| AUTH-03 | P1 / Browser | Required fields and validation | Submit blank fields, a malformed email, and overlong input. | Validation explains the problem; the request does not create a session. |
| AUTH-04 | P0 / Browser | Customer cannot log in as staff | Attempt staff login with a customer-only legacy identity. | Staff access is denied; being a retail customer does not grant membership. |
| AUTH-05 | P0 / Browser | Protected direct URL | While signed out, open /pos, /sales, /team and a saved invoice URL. | The browser requests login; unauthenticated API requests return 401 without records. |
| AUTH-06 | P0 / Browser | Logout and old tabs | Sign out; refresh another previously opened protected tab and use browser Back. | Protected data cannot be reloaded; reuse of the previous session is rejected. |
| AUTH-07 | P1 / Browser | Session expiry | In the isolated environment invalidate the test session, then request data or submit a form. | The app asks for login and handles the failure without duplicating a transaction. |
| AUTH-08 | P1 / API | Login rate limit | Send enough failed logins to reach the configured limiter. | Further attempts receive 429 until the limiter resets; no login is granted. |
| AUTH-09 | P0 / API | CSRF enforcement | Submit an authenticated state-changing request with a missing or invalid XSRF token, including one without Origin. | The mutation is rejected, normally 419; no data changes. |
| AUTH-10 | P0 / API + HTTPS | Cookie and CORS security | Under HTTPS inspect cookie flags and session rotation; send a credentialed request from an unapproved origin. | Secure/HttpOnly session behavior matches staging configuration; an unapproved origin cannot read authenticated responses. |
| AUTH-11 | P1 / Browser | New owner registration | At /register use a unique test email, valid password confirmation and new business details. | Owner and business are created once and signed in; the new business is not automatically granted paid posting access. |
| AUTH-12 | P1 / Browser | Invalid/duplicate registration | Try mismatched passwords, an invalid business slug, an existing email and a duplicate slug. | Requests are rejected clearly and leave no partial owner/business/membership records. |

### TEN — Businesses and tenant isolation

Prerequisites: Prepare business A and business B, an A-only user, a B-only user, and a shared staff identity. Each business needs its own valid access state for mutation tests.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| TEN-01 | P0 / Browser | Create and select business | Create a uniquely named business and inspect Businesses. | It appears only to its authorized members; it starts without another business's stock, customers or sales. |
| TEN-02 | P0 / Browser | Business switch clears workspace | Add an A product to the cart, then switch to B. | Header, permissions and records change to B; A's cart and quote are cleared. |
| TEN-03 | P0 / Browser | Lists and searches are isolated | As A-only staff search catalog, customers, sales, returns, stock and purchasing using B-only identifiers. | No B records or search results are returned. |
| TEN-04 | P0 / API | Direct foreign record IDs | As A request B's product, variant, invoice, return, supplier or expense by ID. | Access fails with the contract's 403/404/validation result; no B details are disclosed. |
| TEN-05 | P0 / API | Foreign record mutations | As A try updating, archiving or posting B's records. | All attempts fail and B's fields, amounts and stock remain unchanged. |
| TEN-06 | P0 / API | Foreign file access | As A request B's product image and private export download URL. | The file is not returned; guessing an ID does not grant access. |
| TEN-07 | P0 / API | Foreign relationships | Submit B's location, customer, unit, role or payment method in an A mutation. | The request is rejected; no cross-tenant relationship is saved. |
| TEN-08 | P0 / API | Untrusted tenant override | Add B's tenant_id to A's query strings, JSON payloads and form fields. | The server continues to resolve A from the authenticated session; B cannot be selected this way. |
| TEN-09 | P0 / API | Unauthorized tenant switch | Submit a switch to a business where the user has no active membership. | Switching fails; the user receives no access to that business. |
| TEN-10 | P0 / Browser | Revoked membership | Suspend a test membership while its browser session is open, then request and mutate data. | The next request revalidates membership and denies access. |
| TEN-11 | P0 / Browser + Ops | Suspended business | Administratively suspend the isolated business; test reads, ordinary posting and recovery routes. | Ordinary writes are blocked; permitted read/billing/export/support recovery follows the documented policy; data remains. |
| TEN-12 | P1 / Browser | Same SKU across businesses | Create the same test SKU and barcode text separately in A and B. | Uniqueness is enforced inside each business; no cross-tenant collision or lookup leak occurs. |

### ROLE — Staff, roles and authorization

Prerequisites: Use owner, admin, manager, cashier, inventory clerk, accountant and viewer test users. Compare with their actual assigned permission codes; existing custom grants may differ.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| ROLE-01 | P1 / Browser | Owner administration | As owner inspect setup, team, billing, approval and reporting access. | Owner permissions permit the expected business administration actions. |
| ROLE-02 | P0 / Browser | Cashier boundaries | As default cashier open POS, customers, shifts and return requests; attempt stock approval, refund approval and billing. | Allowed work succeeds; unauthorized actions are hidden and direct API attempts are rejected. |
| ROLE-03 | P0 / Browser | Inventory clerk boundaries | As inventory clerk draft a stock adjustment/purchase and validate an import; try approval/refund/billing. | Draft/import rights follow assigned permissions; unauthorized financial or approval actions fail. |
| ROLE-04 | P1 / Browser | Accountant boundaries | As accountant inspect reports, expenses, settlements, audit and exports; try unauthorized POS administration. | Only assigned capabilities succeed; read access does not imply permission to post. |
| ROLE-05 | P0 / API | Viewer cannot post | As viewer attempt sale, product edit, stock adjustment, payment and refund through direct requests. | Writes are rejected even when the browser is bypassed. |
| ROLE-06 | P1 / Browser | Custom role dependencies | Create a role with an allowed permission set; try manage permission without its required view permission. | Valid roles save; dependency-invalid combinations are rejected. |
| ROLE-07 | P0 / API | Privilege escalation blocked | As a non-owner attempt to grant permissions you do not hold, assign owner, or edit a foreign business role. | No escalation or foreign role assignment is allowed. |
| ROLE-08 | P0 / Browser | System role protection | Try editing a system role and removing or suspending the final active owner. | The action is blocked; the business retains an active owner. |
| ROLE-09 | P0 / Browser | Reuse existing staff identity | Add an existing staff email to another business using a different role. | The identity is reused without resetting its password/profile or other memberships. |
| ROLE-10 | P0 / Browser | Permission revocation | Remove a custom role permission or change a membership role; refresh and retry an old action. | Menus refresh appropriately and the server denies the revoked action; stale UI does not authorize it. |

### CAT — Products, variants and catalog setup

Prerequisites: Use PKR, EA precision 0, KG precision 3, a category, brand, Size attribute and S/M values. Use unique UAT-prefixed SKUs.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| CAT-01 | P1 / Browser | Create simple product | Create SHIRT-100 with EA, price 100.00, cost 60.00, no tax and inventory tracking. | The product and first SKU save correctly and appear in catalog/search. |
| CAT-02 | P1 / Browser | Edit product and variant | Update the description, price, cost and active flag; reload the product. | Allowed changes persist; existing invoice snapshots retain their original data. |
| CAT-03 | P1 / Browser | Variable products | Create a variable shirt with distinct S and M SKUs; assign matching Size values. | Each variant keeps its own identity, price, barcode and stock. |
| CAT-04 | P0 / Browser | Service and digital products | Create SERVICE-50 and a digital SKU without inventory tracking; sell each. | Sales work without creating or reducing physical inventory balances. |
| CAT-05 | P1 / Browser | SKU uniqueness | Try another SKU equal to an existing one in the same business, including normalized case. | The duplicate is rejected; there is one SKU identity. |
| CAT-06 | P1 / Browser | Barcode assignment | Assign a barcode; scan it in POS; then try assigning it to a second SKU in the same business. | The scan finds one exact SKU and the duplicate assignment is rejected. |
| CAT-07 | P1 / Browser | Invalid monetary input | Enter negative cost/price, text, scientific notation, and more than supported precision. | Invalid values are rejected; decimal strings within the supported range persist. |
| CAT-08 | P1 / Browser | Unit precision | Sell/adjust an EA quantity of 1.5 and a KG quantity of 0.125; then try KG 0.0001. | EA fractional and excess-precision KG quantities fail; KG 0.125 is accepted where stock permits. |
| CAT-09 | P1 / Browser | Categories and hierarchy | Create a parent and child category; attempt self-parenting and a category cycle. | Valid hierarchy saves; cycles and cross-tenant parents are rejected. |
| CAT-10 | P1 / Browser | Brands | Create/edit/deactivate a brand and inspect related product forms. | Data persists and existing product/history references remain readable. |
| CAT-11 | P1 / Browser | Attribute mismatch | Assign a value from another attribute to Size, including a foreign-tenant value. | The mismatch is rejected; variant attributes remain consistent. |
| CAT-12 | P1 / Browser | Image upload and isolation | Upload valid PNG/JPEG/WebP, inspect the download, and try a non-image payload or a foreign image ID. | Valid images are available only as authorized; invalid content and foreign access are rejected. |
| CAT-13 | P0 / Browser | Archive instead of delete | Archive a product used in a sale; inspect catalog and original receipt. | It is unavailable for new selling as appropriate; history and references remain intact. |
| CAT-14 | P1 / Browser | Inactive item selling | Deactivate a product/variant, then scan or submit its ID for a new quote. | It cannot be sold through a new transaction; historical receipts remain readable. |
| CAT-15 | P0 / API | Used metadata guards | After posting stock/sales attempt to change the unit, tracking mode, inventory flag or used location ownership. | Changes that would invalidate history are rejected. |
| CAT-16 | P1 / Browser | Price list tiers | Configure catalog price 100, tier 90 at quantity 5 and tier 80 at quantity 10; quote 4, 5, 9 and 10. | The highest eligible tier applies: 100, 90, 90 and 80 per unit. |
| CAT-17 | P1 / Browser | Tax class and effective rate | Configure TAXED-100 with a 10% class, matching TEST jurisdiction and valid dates. | A valid quote taxes the discounted base; missing, inactive, ambiguous or expired configuration blocks taxed sales. |
| CAT-18 | P1 / Browser | Overlapping rates and currency | Try overlapping rates for one class/jurisdiction and an inactive or different-currency price list. | Invalid tax overlaps and unsuitable price lists are rejected rather than silently selected. |

### SET — Branches, registers and POS activation

Prerequisites: Use a disposable restored legacy business. Activation affects old writers and is deliberately not a routine reversible setting.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| SET-01 | P1 / Browser | Create branch/location | Create UAT-MAIN, its store and a warehouse; edit permitted address/status fields. | Records persist with the correct business/branch ownership. |
| SET-02 | P1 / Browser | Register setup | From POS activation open Cash registers and create a register for UAT-MAIN's store. | The register references the intended branch/store and is selectable for shifts. |
| SET-03 | P0 / Browser | Invalid register location | Try a foreign location, a warehouse or a store attached to another branch. | Invalid associations cannot be used for POS posting. |
| SET-04 | P0 / Browser | Activation preview | Open the legacy branch preview before activation and inspect mappings, opening rows and issues. | The preview lists the actual source quantities/location mapping and does not change stock. |
| SET-05 | P0 / Browser | Activation approval | Review source rows and physical locations; confirm the checkbox, tax jurisdiction and credit setting. | One activation succeeds and opening stock is posted once with source references. |
| SET-06 | P0 / Browser | Unreviewed/drifted activation | Omit confirmation or change source quantities/identity/prices after preview; submit the old preview hash. | Activation fails and does not create a partial opening batch. |
| SET-07 | P0 / Browser | Repeat activation | Retry the same activation and then repeat with another request key. | No second opening stock posting occurs. |
| SET-08 | P0 / Browser | Legacy writer freeze | After first legacy activation try old Blade stock, product, school, order and return mutations, including GET delete/cancel routes. | Affected old writers are rejected; historical reads remain available. |
| SET-09 | P0 / Browser | Branch credit configuration | Activate one branch with credit enabled and another with credit disabled; attempt named-customer partial sales. | Only the permitted branch accepts named-customer credit; walk-ins still require full payment. |
| SET-10 | P0 / Browser | Inactive branch/register | Disable a test branch/register and try opening a shift or completing a new quote/checkout. | New posting is blocked without changing historical shifts/sales. |

### LEG — Legacy mapping and source preservation

Prerequisites: Use a restored, populated legacy fixture with known school/product/size stocks, duplicates, historical orders, two ledger namespaces and ambiguous customer identities.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| LEG-01 | P0 / Browser | Distinct school SKUs | Map equal product/size labels belonging to two schools under the approved policy. | Each legacy school/product/size stock row retains a distinct SKU and original school context. |
| LEG-02 | P1 / Browser | Mapping evidence | Inspect mapped source IDs, school labels, images, prices, margins and enum values. | Original values are preserved; ambiguous meanings are not guessed. |
| LEG-03 | P0 / Browser | Mapping retry | Run catalog mapping twice for the same reviewed source data. | No duplicate products, variants or source mappings appear. |
| LEG-04 | P0 / Browser | Unresolved source rows | Include a missing relation, duplicate code collision or otherwise invalid stock row and run mapping. | The problem is listed for review; it is not silently merged or discarded. |
| LEG-05 | P0 / Browser | Customer/staff separation | Map a clear retail buyer, staff-referenced order and ambiguous/missing user. | Clear buyers map safely; staff/ambiguity require review; credentials and original order references remain intact. |
| LEG-06 | P0 / Browser | One opening balance | Activate against current source stock and compare opening quantities; inspect historical order quantities. | Only current opening stock is imported; historical sales are not deducted again. |
| LEG-07 | P0 / Ops | History preservation | Compare pre/post counts and original financial fields, returned counters and IDs. | Source values and relationships remain unchanged; discrepancies block acceptance. |
| LEG-08 | P0 / Browser | Ledger namespaces | Capture legacy finance evidence when both ledger and ledgers contain rows. | Namespaces remain separate, balance ambiguity is listed, and no automatic accounting merge occurs. |
| LEG-09 | P0 / Browser | Unknown refunds | Include historical returned quantities without payment-method/time evidence. | Refund details remain unknown; no invented cash/card refund or payment is created. |
| LEG-10 | P1 / Browser | New sales versus old orders | Compare the new Sales list to preserved legacy order screens/evidence before Phase 8 historical conversion. | New POS sales are displayed; missing historical conversion is recorded as pending, not mistaken for deletion. |

### INV — Stock, adjustments, counts and reservations

Prerequisites: For each quantity test use a fresh EA SKU with 20 units, except where the steps specify another balance. Record movement totals and available quantity before the case.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| INV-01 | P0 / Browser | Opening/current balance | Inspect balances and source movements for the fixture. | On-hand is 20 and available equals on-hand minus active reservations. |
| INV-02 | P0 / Browser | Positive adjustment approval | Create a +5 adjustment; inspect before and after authorized posting. | Draft does not change stock; one approval raises stock to 25 with a +5 movement. |
| INV-03 | P0 / Browser | Negative adjustment | Post -3 with a reason, then attempt a reduction below available stock. | Valid balance becomes 17; the invalid reduction leaves balance and movements unchanged. |
| INV-04 | P0 / Browser | Unauthorized/repeated approval | As clerk attempt approval; as approver post once then retry. | Clerk is denied; repeat approval does not post twice. |
| INV-05 | P0 / Browser | Physical count variance | With 20 expected, record 18 counted and post. | One -2 movement produces on-hand 18; count evidence retains expected/counted/variance. |
| INV-06 | P0 / Browser | Stale physical count | Create a count at 20, post a sale or receipt, then try to post the old count. | The stale version is rejected and no incorrect correction is posted. |
| INV-07 | P0 / Browser | Reserve stock | Reserve 5 of 20 for 15 minutes; inspect availability and try selling 16. | On-hand remains 20, reserved 5, available 15; consuming more than available fails. |
| INV-08 | P1 / Browser | Release and expiry | Release a reservation twice; create another, let it expire and trigger availability-sensitive processing. | Held stock is released once; expiry does not create physical stock movements. |
| INV-09 | P1 / Browser | Over-reservation | Try to reserve 21 of 20 and use zero, negative or excess-precision quantities. | Invalid reservations fail without reducing available stock. |
| INV-10 | P0 / Browser | Movement traceability | Post stock-affecting purchases, sales, returns and adjustments; inspect movement history. | Each physical change has a matching source, signed quantity, time and actor. |
| INV-11 | P0 / Browser | Stock reconciliation | Run Reconcile ledger after stock transactions and reservations. | Movement totals match stored on-hand and active reservation totals; issues are reported without rewriting history. |
| INV-12 | P0 / Ops | Immutable movement history | In an isolated developer test attempt to edit/delete a MariaDB movement directly. | Database protection rejects the modification; corrections require a new posting. |
| INV-13 | P0 / API | Same request key and changed payload | Repeat one adjustment with the same key/data, then change quantity while retaining the key. | Identical retry posts once; changed-payload reuse conflicts. |
| INV-14 | P1 / Browser | Unsupported tracking mode | Set up batch/serial catalog metadata and try inventory/checkout posting for that SKU. | The unsupported transaction is explicitly rejected; no misleading partially tracked movement is saved. |

### PUR — Suppliers, purchasing and payables

Prerequisites: Use SUPPLIER-A, a fresh EA SKU with zero opening stock for receipt golden cases, destination store, cost 60.00, zero purchase tax and unique requests. Supplier settlements record confirmed external payments.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| PUR-01 | P1 / Browser | Supplier maintenance | Create/edit a supplier with contact, address, payment terms and active flag. | Changes persist within the business; invalid/foreign suppliers cannot be used. |
| PUR-02 | P1 / Browser | Draft purchase order | Create an order for 20 units at cost 60.00. | Draft total is 1200.00; stock and payables remain unchanged. |
| PUR-03 | P0 / Browser | Order approval | Approve as authorized staff; try receiving the unapproved version in a separate fixture. | Only an approved order can receive goods; approval itself does not add stock. |
| PUR-04 | P0 / Browser | Partial accepted/rejected delivery | Receive 8 accepted and 2 rejected units against the 20-unit order. | Stock increases by 8, payable is 480.00, and 12 accepted units remain outstanding; rejected units add neither stock nor payable. |
| PUR-05 | P0 / Browser | Complete remaining delivery | Receive the remaining 12 accepted units. | Total accepted reaches 20, stock increases by 12 more, and the order completes. |
| PUR-06 | P0 / Browser | Over-receipt and invalid quantity | Try receiving more than the remaining order amount, a negative quantity or invalid unit precision. | The entire invalid receipt fails; no stock/payable/invoice fragments remain. |
| PUR-07 | P0 / API | Duplicate receipt | Submit the same receipt request key/data twice, including a lost-response retry. | One receipt, stock addition and supplier obligation exist. |
| PUR-08 | P0 / Browser | Supplier return | Against the 8-unit receipt return 2 accepted units. | Stock decreases by 2 and that receipt's obligation reduces by 120.00. |
| PUR-09 | P0 / Browser | Invalid supplier return | Return more than accepted-minus-returned or more than current available stock. | The return fails atomically and does not create a credit or movement. |
| PUR-10 | P1 / Browser | Partial supplier settlement | Against an outstanding 480.00 record a confirmed 100.00 payment/reference. | Outstanding becomes 380.00; a settlement record is retained without sending bank funds. |
| PUR-11 | P0 / Browser | Overpayment and settlement retry | Try paying more than outstanding; retry a valid settlement with the same key. | Overpayment fails and a valid retry creates one settlement. |
| PUR-12 | P1 / Browser | Return after settlement | Fully settle the 480.00 receipt, then return 2 units worth 120.00. | A supplier credit is explicitly retained; previous payment history is not erased. |
| PUR-13 | P0 / API | Draft/approval permissions | As cashier or viewer attempt order creation/approval, receipt posting, supplier return and settlement. | Each action follows its own server permission; hidden buttons are not the only control. |
| PUR-14 | P0 / Browser | Receipt-return reconciliation | Compare accepted quantities, cumulative returns, outstanding supplier balances and stock movements. | All totals reconcile; rejected units and recorded payments have the documented separate effects. |

### POS — Point of sale, quotes and checkout

Prerequisites: Use an activated test branch/register, open shift, SHIRT-100 price 100.00/cost 60.00/no tax and 20 units. Use independent fixtures per numerical case.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| POS-01 | P1 / Browser | Scanner exact match | Focus scanner input, scan a valid SKU/barcode and press Enter. | Exactly the matched product is added; barcode text does not become an arbitrary price. |
| POS-02 | P1 / Browser | Search and unknown scan | Search by product name; scan an unknown/ambiguous identifier. | Search allows explicit selection; invalid exact scan gives feedback without adding a wrong SKU. |
| POS-03 | P1 / Browser | Cart edits | Add/remove items and change quantities before checkout. | The cart updates and any prior quote is invalidated. |
| POS-04 | P0 / Browser | Cash sale | Quote and sell 2 SHIRT-100 units for cash 200.00. | One completed sale/invoice/payment exists; stock becomes 18 and expected cash rises by 200.00. |
| POS-05 | P0 / Browser | Missing prerequisites | Try a quote without an open shift, from an inactive/unactivated branch, or with insufficient stock. | The server rejects the sale; no stock/payment/invoice posting occurs. |
| POS-06 | P0 / Browser | Zero/negative/fractional quantities | Submit zero, negative, invalid text and EA 1.5. | Invalid quantities fail without side effects. |
| POS-07 | P0 / API | Authoritative prices | Using a controlled request add forged unit_price, grand_total or tax values to a quote/checkout payload. | The server ignores untrusted totals or rejects the payload and calculates from its configured catalog. |
| POS-08 | P0 / Browser | Discount and tax golden case | Quote 2 TAXED-100 units at 100 each, fixed order discount 20.00 and tax 10%. | Subtotal 200.00, discount 20.00, taxable base 180.00, tax 18.00, grand total 198.00. |
| POS-09 | P0 / API | Discount permission and bounds | As default cashier attempt a discount through the API; as authorized staff submit excessive/invalid discounts. | Unauthorized and invalid discounts fail; no negative sale total is created. |
| POS-10 | P0 / Browser | Half-up rounding | Quote 2 units of CENT-123456 priced 12.3456 without tax/discount. | The extended total is 24.69 using decimal half-up rounding; no float artifacts appear. |
| POS-11 | P0 / Browser | Expired quote | Obtain a quote, wait more than its 10-minute validity or use a controlled expired fixture, then checkout. | Checkout rejects it and requests a fresh quote; no sale is partially posted. |
| POS-12 | P0 / Browser | Repriced or changed quote | Quote, then change a price/tax/quantity-related source configuration before checkout. | A changed calculation cannot silently complete at stale totals; re-quote is required. |
| POS-13 | P0 / Browser | Quote does not reserve stock | Quote an item, sell its final available stock in another session, then use the first quote. | The stale availability does not oversell; checkout checks stock again. |
| POS-14 | P0 / Browser | Duplicate checkout | Rapidly double-click Complete sale and retry the same API key. | One sale, invoice, stock deduction and payment are recorded. |
| POS-15 | P0 / Browser | Lost checkout response | Interrupt the response after submission; use Retry same checkout without changing payment details. | The same request key/data returns the original result or safely completes once. |
| POS-16 | P0 / API | Changed checkout payload/key reuse | Resubmit a used key with a changed quote or payment amount. | A conflict is returned; it cannot produce another sale. |
| POS-17 | P0 / API | One sale per quote | Use another request key to checkout an already consumed quote. | No second sale is created from that quote. |
| POS-18 | P0 / API | Atomic rollback | Force a stock-validation failure during checkout in an isolated fixture and compare tables. | Sale, payments, movements and invoice sequence changes roll back together. |

### PAY — Customers, collections, invoices and receipts

Prerequisites: Use SHIRT-100 price 100.00, a named customer ALICE, cash/card/bank methods and an open shift. An external reference means the payment was confirmed outside this app.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| PAY-01 | P1 / Browser | Customer CRUD and identity | Create/edit ALICE with phone/email/notes; deactivate the customer; inspect staff logins. | Customer fields persist in the business; creating a customer does not create staff access. |
| PAY-02 | P0 / Browser | Full walk-in payment | Try checkout of 200.00 with no payment and with 199.00; then use 200.00. | Walk-in underpayments fail; full payment succeeds. |
| PAY-03 | P0 / Browser | Named-customer credit | At the credit-enabled branch sell 200.00 to ALICE with 50.00 collected. | Sale completes with paid 50.00 and balance due 150.00. |
| PAY-04 | P0 / Browser | Disabled credit policy | At a credit-disabled branch try the same named-customer 50.00 payment on a 200.00 sale. | Partial/unpaid sale is rejected; branch policy is enforced on the server. |
| PAY-05 | P0 / Browser | Split payment | Pay a 200.00 sale using cash 120.00 and confirmed card 80.00 with a reference. | Two payment records sum to 200.00; drawer cash increases only by 120.00. |
| PAY-06 | P1 / Browser | External payment reference | Omit the terminal/bank reference, then provide a confirmed test reference. | Missing non-cash confirmation is rejected; a valid record does not initiate a card/bank charge. |
| PAY-07 | P0 / Browser | Later balance collection | On a named-customer sale owing 150.00 collect 100.00, then 50.00 in an open shift in the original branch. | Due decreases to 50.00 then 0.00 and status becomes paid; stock is not deducted again. |
| PAY-08 | P0 / Browser | Overpayment and invalid shift | Try collecting more than due, using a closed shift, another branch or another tenant's method. | The invalid payment fails with no collection/stock side effects. |
| PAY-09 | P0 / API | Later payment retry | Retry one balance collection with the same key and amount, then change the payload using that key. | One payment is recorded; changed reuse conflicts. |
| PAY-10 | P1 / Browser | Receipt snapshots and printing | Print a receipt; then edit product/customer/receipt settings and reopen the original invoice. | Original sale snapshots and amounts remain; print layout contains the original invoice, items, totals and recorded payments. |
| PAY-11 | P0 / Browser | Invoice sequencing and due filter | Complete several sales in the same branch, inspect invoice numbers, then use Show balances due only. | Invoice numbers are unique in their scope and only currently owing invoices appear in the due filter. |
| PAY-12 | P0 / Browser | Initial overpayment and applied cash | On a 200.00 checkout submit 300.00 as the applied payment; then use the actual applied amount 200.00 after giving any change externally. | An over-applied payment is rejected; the app does not infer change from an overpayment field. |
| PAY-13 | P0 / API | Invalid payment amounts | On checkout and later collection submit zero/negative amount, invalid precision, inactive method and missing payment details. | Invalid entries are rejected with no sale/payment/stock fragments. |

### RET — Retail returns, cancellation, refunds and exchanges

Prerequisites: Use independent completed sales with original line references. Default return_days is blank. For basic cases sell 2 SHIRT-100 for 200.00; refund cases require authorized staff and an open original-branch shift.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| RET-01 | P0 / Browser | Request-approve-post lifecycle | Request one resellable unit, inspect draft, approve, then post. | Draft/approval do not restock or credit; posting adds one unit and creates credit 100.00 with separate timestamps. |
| RET-02 | P0 / Browser | Return permissions | As cashier request a return; attempt approval/post/refund without those grants. | Request follows assigned permission; unauthorized approvals and refunds fail server-side. |
| RET-03 | P0 / API | Original line ownership | Request a return using another sale's line or another tenant's line. | The request is rejected; no unrelated line is credited. |
| RET-04 | P0 / Browser | Overreturn and invalid quantities | Try returning 3 from a 2-unit line, negative/zero quantity, a repeated line ID or invalid precision. | The return fails without stock or financial changes. |
| RET-05 | P0 / Browser | Repeat post and cumulative returns | Post one unit, repeat posting, then return the final unit and attempt another return. | Two sold units are returned at most; retries cannot restock twice. |
| RET-06 | P0 / Browser | Damaged quarantine | Post a one-unit damaged return. | A quarantine record is created; sellable on-hand does not increase. |
| RET-07 | P0 / Browser | Original tax and discount | Return the entire two-unit golden sale with subtotal 200, discount 20, tax 18 and total 198 after changing catalog prices. | Cumulative credit is 198.00, returned discount 20.00 and returned tax 18.00; current catalog price is irrelevant. |
| RET-08 | P0 / Browser | Split-return rounding | Sell 2 CENT-123456 units for 24.69; post one-unit returns separately. | Credits are 12.35 then 12.34 and sum to 24.69, without extra cents. |
| RET-09 | P0 / Browser | Credit reduces receivable first | Sell 2 SHIRT-100 to ALICE for 200 with only 50 collected; return 1 unit and try a cash refund. | Remaining invoice value is 100, due is 50 and refund due is 0; cash refund is rejected. |
| RET-10 | P0 / Browser | Refund capped by actual collections | Continue the previous fixture by returning the second unit, then refund 50 and try another 0.01. | Only the collected 50.00 is refundable; the further refund fails. |
| RET-11 | P0 / Browser | Cash/card/bank refund | On separate fully paid fixtures record eligible cash and externally confirmed card/bank refunds; omit the non-cash reference in a negative case. | Valid refunds update recorded balances once; only cash affects the drawer; a required external reference cannot be omitted. |
| RET-12 | P0 / API | Refund bounds and retry | Try zero/negative/excess refunds and retry one valid refund with the same key. | Invalid requests have no effects; the valid refund is recorded once. |
| RET-13 | P0 / Browser | Refund shift and cash availability | Use a closed/foreign/wrong-branch shift or refund more cash than the expected drawer holds. | Posting is blocked; original stock, credit and cash records remain coherent. |
| RET-14 | P0 / Browser | Full cancellation | Cancel an untouched completed sale with a reason, retry it, then try another cancellation key. | One full return/void occurs; stock restores once; cash is refunded only by a separate eligible refund record. |
| RET-15 | P0 / Browser | Cancellation after partial return | Post a partial return, then attempt full cancellation. | Cancellation is blocked; the previously returned units are not restocked again. |
| RET-16 | P1 / Browser | Return window configuration | First request an older eligible sale with no day limit; then set a finite limit and request a sale beyond it. | Blank policy imposes no fixed limit; a newly requested return beyond the configured elapsed window is blocked. |
| RET-17 | P0 / Browser | Exchange same customer/branch | Post/approve a return with collected credit 100; quote a replacement worth 150 for the same customer/branch and collect 50. | One linked replacement uses 100 internal credit plus 50 external payment; stock, credit and collections commit together. |
| RET-18 | P0 / API | Invalid/repeated exchange | Try another customer/branch, fake internal exchange payment through normal checkout, an underpaid walk-in replacement, or exchange the same return again. | Invalid attempts fail atomically; the linked return cannot fund two replacements; same-key retry returns its original result. |
| RET-19 | P0 / Browser | Cheaper replacement and remaining credit | Use a return with 100.00 available collected credit for a same-customer/branch replacement worth 50.00; inspect and refund the remaining eligible 50.00. | Only 50.00 transfers to the replacement; remaining refundable credit is preserved and can be refunded once. |

### CASH — Register shifts and cash reconciliation

Prerequisites: Use a fresh active register and open shift with 1000.00. Each case starts with its stated independent transactions.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| CASH-01 | P1 / Browser | Open shift | Count and open with 1000.00; inspect register and session. | Exactly one open shift is recorded with actor, time and opening cash. |
| CASH-02 | P0 / Browser | Duplicate/open-shift protection | Retry the same open request and try opening another shift for the same register. | The original retry is safe and a second simultaneous open shift is blocked. |
| CASH-03 | P0 / Browser | Cash versus external methods | Record cash 200 plus independently confirmed card/bank payments. | Expected drawer cash rises to 1200.00 only from cash; external methods appear separately. |
| CASH-04 | P1 / Browser | Cash in/out | Record cash in 50.00 and cash out 20.00 with reasons. | Expected cash changes by net +30.00; signed movement records remain traceable. |
| CASH-05 | P0 / Browser | Invalid cash movement | Attempt zero/negative/over-precision amounts, excessive withdrawal and a duplicate movement retry. | Invalid amounts/overdraw fail; a valid same-key movement posts once. |
| CASH-06 | P0 / Browser | Cash expense/refund integration | From opening 1000, collect cash 200, refund cash 100, add 50, remove 20 and approve cash expense 30. | Expected cash is exactly 1100.00, with every component visible in reconciliation. |
| CASH-07 | P0 / Browser | Close and variance | Continue the previous golden shift, count 1095.00 and close. | Stored expected is 1100.00, counted 1095.00, variance -5.00 and shift status is closed. |
| CASH-08 | P0 / Browser | Close retry and posting freeze | Retry the same close request; attempt new sale/payment/refund/movement/linked cash expense against the closed shift. | The snapshot is not duplicated and new postings into that shift are rejected. |
| CASH-09 | P1 / Browser | Next shift | Open a fresh shift on the same register after closing. | A new independent session opens; previous closing totals remain unchanged. |
| CASH-10 | P0 / Browser | Shift ownership permissions | As another cashier inspect/use a shift without cash.manage; repeat as an authorized manager. | Access follows shift ownership and permissions, independent of guessing the session ID. |
| CASH-11 | P1 / Browser | History display | Enable Include closed shifts and reopen the closed session's reconciliation. | Closed shifts and their stored count/expected/variance remain readable. |

### EXP — Expense categories, drafts and approvals

Prerequisites: Use an active expense category, branch, cash/card/bank method and an open shift when cash is selected.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| EXP-01 | P1 / Browser | Category creation | Create a category such as UAT-Supplies and refresh. | The category is available only in the selected business. |
| EXP-02 | P1 / Browser | Draft cash expense | Save a cash expense of 30.00 in an open shift; inspect drawer before approval. | Draft is stored without reducing expected cash. |
| EXP-03 | P0 / Browser | Approve cash expense | Approve the draft and repeat approval. | Expected cash decreases by 30.00 once; expense receives approval/posting evidence. |
| EXP-04 | P1 / Browser | Confirmed external expense | Record a bank/card expense with a confirmation reference, then approve. | It appears in reports without reducing register cash or initiating a bank transfer. |
| EXP-05 | P1 / Browser | Invalid expense inputs | Try zero/negative amount, invalid precision, missing external reference and cash without a shift. | Invalid drafts/postings are rejected with no financial side effects. |
| EXP-06 | P0 / Browser | Insufficient cash/closed shift | Try approving a cash expense larger than expected cash or tied to a closed shift. | Approval fails and the drawer is not overdrawn. |
| EXP-07 | P0 / API | Branch/tenant mismatch | Use a shift from another branch or foreign category/method in an expense. | The mismatch is rejected without changing either business. |
| EXP-08 | P0 / API | Expense authorization | As staff with expenses.view only attempt creation and approval using direct requests. | Read permission alone grants neither creation nor approval. |

### REP — Financial reports and reconciliation

Prerequisites: Use a separate report fixture with one fully paid no-tax 200.00 sale, one 100.00 return/refund, a posted 30.00 cash expense and no other period activity.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| REP-01 | P0 / Browser | Golden financial totals | Run Reports for the fixture's tenant-local date. | Invoiced sales 200, return credits 100, net sales 100, external collections 200, external refunds 100, net external collections 100 and expenses 30 reconcile. |
| REP-02 | P0 / Browser | Tax and discount totals | Report the 198.00 golden taxed/discounted sale and its matching full return. | Original and returned tax/discount totals agree; net sale/tax become zero after full reversal. |
| REP-03 | P0 / API + fixture | Dates and tenant timezone | Use fixture activity just before/after midnight in the tenant's timezone and report each day. | Each record belongs to the correct inclusive local day; the end boundary is next-day exclusive. |
| REP-04 | P0 / API + fixture | Older invoice collection | Create an invoice on day 1 and collect/refund on day 2; report day 2. | Day 2 shows its recorded money activity without counting the original invoice as a new day-2 sale. |
| REP-05 | P1 / Browser | Lifetime balances are labeled | Report a narrow period with older unpaid sales still present. | Current receivable/refund obligations remain explicitly lifetime balances, distinct from period activity. |
| REP-06 | P0 / Browser | Exchange is not external cash | Report a linked exchange funded by internal return credit and a top-up. | Internal credit in/out is separated from external collections/refunds; it does not invent cash. |
| REP-07 | P1 / Browser | Cost and damaged returns | Compare no-tax two-unit sale at cost 60 each with resellable versus damaged return fixtures. | Only resellable return cost is reversed; damaged goods retain cost; margin is labeled an estimate. |
| REP-08 | P1 / Browser | Invalid and empty filters | Submit reversed dates, an overlong range, malformed dates and a valid empty date range. | Invalid filters are rejected; a valid empty range displays zero period totals without crashing. |
| REP-09 | P0 / API | Branch and tenant scoping | Use the documented API branch_id filter and foreign IDs; compare A/B reports. | Only the selected business/valid branch contributes; foreign filters do not leak figures. |
| REP-10 | P0 / API + fixture | Reconciliation discrepancy handling | In an isolated developer fixture create a known aggregate/posted-record discrepancy and run the report. | The discrepancy is flagged with review evidence; the report does not silently rewrite history. |

### BILL — Stripe SaaS subscriptions and entitlements

Prerequisites: Use Stripe test mode only, configured actual test Price IDs, a correctly signed webhook endpoint, a running queue and mapped Portal prices. Most real-account cases are BLOCKED until these exist. Access-state cases use a non-grandfathered verified subscription unless they explicitly test legacy access.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| BILL-01 | P1 / Browser | Provisional plans and toggle | As owner open Plans & billing; toggle monthly/yearly and inspect Starter/Professional/Business/Enterprise. | Configured provisional terms/limits display correctly; no secret or React-hardcoded Stripe Price ID is exposed. |
| BILL-02 | P0 / Browser | Unconfigured Checkout | With test keys/Price IDs unset try a paid plan through UI and API. | Checkout is disabled/rejected clearly; no customer/subscription/charge/access grant is created. |
| BILL-03 | P0 / Browser | New versus existing access | Inspect an existing preserved-access tenant and a newly registered tenant without payment. | Existing grandfathered access is retained; new tenant posting stays read-only until verified entitlement. |
| BILL-04 | P0 / API | Billing authorization and tenant override | As cashier open billing endpoints; as owner add another tenant ID to requests. | Unauthorized billing access is rejected; the server-selected tenant remains authoritative. |
| BILL-05 | P0 / Stripe | Valid hosted Checkout | Select a configured plan and continue using a successful Stripe test payment method. | The app redirects to Stripe-hosted subscription Checkout; the app does not collect card details. |
| BILL-06 | P0 / API + Stripe | Price mismatch or invalid plan | Try invalid plan IDs and a test Price whose amount, currency or recurring interval differs from local terms. | Requests fail before unintended Checkout/entitlement changes. |
| BILL-07 | P0 / Stripe | Customer reuse | Retry Checkout for the same tenant and inspect the Stripe sandbox customer. | The tenant reuses its stable customer identity; another tenant is not attached to it. |
| BILL-08 | P0 / API + Stripe | Duplicate Checkout | Double-click and resend the same key; also try another key while an attempt is pending. | One active attempt is used; duplicate subscription opportunities are blocked. |
| BILL-09 | P0 / API + fixture | Unknown Checkout result | Simulate a timeout around session creation; retry the same key and attempt to expire a preparing/unknown session. | No duplicate subscription or premature release occurs; unresolved attempts require reconciliation. |
| BILL-10 | P0 / Browser | Success URL cannot activate | Visit /billing/success manually without payment or before the webhook worker runs. | The page reads/polls status only; no paid access is granted by navigation. |
| BILL-11 | P1 / Stripe | Cancel/resume Checkout | Cancel a known open test Checkout, return to the app and resume/expire it using supported controls. | Existing access is unchanged; the known pending session is handled without duplicate subscriptions. |
| BILL-12 | P0 / Stripe | Verified initial activation | Finish the valid test Checkout and process its genuine signed events. | Authoritative paid/trial state updates the correct tenant subscription/limits/invoice exactly once. |
| BILL-13 | P0 / API + fixture | Webhook signature/raw body | Send missing/bad/stale signatures or alter the raw body; then send a valid signed test event. | Invalid events fail before updates; a valid event is accepted for queued processing. |
| BILL-14 | P0 / API + Stripe | Duplicate signed webhook | Redeliver the same actual Stripe event/raw payload several times. | Provider event, invoice, payment and entitlement effects are not duplicated. |
| BILL-15 | P0 / API + fixture | Out-of-order delivery | Deliver an older invoice-failure/subscription event after a later paid state and reconcile. | The server fetches current Stripe state instead of reverting access from stale payload order. |
| BILL-16 | P0 / API + fixture | Cross-tenant customer/metadata | Send a signed fixture whose customer binding or tenant_ref conflicts with the attached subscription. | Synchronization fails or safely ignores unrelated events; no other tenant receives access/invoices. |
| BILL-17 | P0 / Ops + fixture | Queue outage and retry | Stop only the UAT worker, receive a valid event, then restart; simulate transient API failure and retry. | A verified event survives, retries with sanitized errors and eventually applies once; pending payment alone grants no access. |
| BILL-18 | P0 / API + fixture | Recurring payment failure/grace | For a paid non-grandfathered fixture simulate invoice.payment_failed and advance the fixture beyond configured grace. | Past-due grace follows policy; after expiry posting is read-only and data/recovery access remain. |
| BILL-19 | P0 / API + fixture | Trial and terminal states | Use controlled verified fixtures for trialing, unpaid, incomplete, incomplete_expired, paused and canceled. | Valid trials permit policy access; expired trials and disallowed states deny ordinary posting without deleting records. |
| BILL-20 | P0 / Stripe + fixture | Cancellation at period end | Schedule cancellation through test Portal and verify state before/after the effective timestamp. | Access remains until the policy cutoff and stops afterward; invoices/history are preserved. |
| BILL-21 | P0 / Stripe + fixture | Upgrade/downgrade payment policy | Use mapped Portal prices; simulate unpaid then paid upgrade and a scheduled/applied downgrade. | Unpaid upgrade retains the previously verified plan/period; verified changes update entitlements at the effective time. |
| BILL-22 | P0 / API + fixture | Backend resource quotas | At reviewed low product/staff/store limits try one extra product, active membership, branch, import and membership reactivation through UI/API. | The server blocks resource growth beyond limits; existing data and suspended identities remain preserved. |
| BILL-23 | P0 / API + fixture | Module gates and recovery | Disable imports/returns/reports for a test plan; then use an expired-access tenant's permitted billing/export/support and ordinary writes. | Feature gates and read-only policy are enforced in Laravel; recovery routes do not become general posting bypasses. |
| BILL-24 | P0 / API + fixture | Portal and live-mode guards | Open Portal for the correct test customer; use a live-prefixed dummy configuration/event in isolated automated fixtures while live mode is disabled. | Portal is tenant-bound and Stripe-hosted; live mode is rejected without a real live call or charge. |
| BILL-25 | P0 / API + fixture | Required event and asynchronous payment matrix | Using verified isolated fixtures exercise checkout.session.completed, async_payment_succeeded/failed, subscription.created/updated/deleted and invoice.paid/payment_failed; include complete-but-unpaid Checkout. | All eight supported types queue/reconcile safely; asynchronous success grants access only from authoritative eligible state, and incomplete/failed payment does not grant paid access. |
| BILL-26 | P0 / API + fixture | Unknown mapped price recovery and lower limits | Reconcile a test subscription with an unconfigured Price, then map a reviewed Price and retry. Separately downgrade a fixture below its current usage. | Unknown mapping fails safely until configured; retry applies once; lower limits retain existing records while blocking extra capacity. |

### TOOL — Workspace settings, alerts, audit, CSV and support

Prerequisites: Use Workspace tools with the required per-tab permissions. Import/export fixtures use fake names and no real credentials or customer information.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| TOOL-01 | P1 / Browser | Receipt settings snapshots | Set receipt name/footer/tax registration, sell an item, change settings and inspect old/new receipts. | New sales use new branding; previous receipts retain their stored branding. |
| TOOL-02 | P1 / Browser | Return/low-stock settings validation | Save blank return_days and threshold, then valid finite values and invalid negative/text input. | Blank/default policies and valid values persist; invalid settings are rejected. |
| TOOL-03 | P1 / Browser | Low-stock alert | Set a threshold above the fixture's resulting available stock and post a stock-changing transaction. | One relevant in-app alert is generated with SKU/quantity; duplicate source retry does not create another alert. |
| TOOL-04 | P1 / Browser | Disabled alert template | Disable the stock.low template and trigger another qualifying event. | The disabled template suppresses its notification; it does not fall back to an enabled default. |
| TOOL-05 | P1 / Browser | Template safety | Save supported placeholders, then try an unknown placeholder or executable-looking text. | Allowed literal substitution works; unknown variables are rejected and text never executes as PHP/Blade/script. |
| TOOL-06 | P0 / Browser | Audit evidence and scope | Create/approve/post several test actions and inspect Audit as another tenant and a user without audit.view. | Authorized records show actor/time/action; unauthorized or foreign audit access is blocked. |
| TOOL-07 | P0 / API + Ops | Request tracing and secret handling | Capture X-Request-ID for a failed request and inspect corresponding operator logs using dummy test secrets. | The ID helps trace the request; logs/audit APIs do not expose passwords, Stripe secrets, cookies or full request bodies. |
| TOOL-08 | P0 / Browser | Valid catalog CSV | Upload the documented UTF-8 CSV with two unique SKUs and existing active unit codes; validate then post. | Preview changes no products/stock; posting creates two products once and no opening inventory. |
| TOOL-09 | P0 / Browser | Invalid CSV all-or-nothing | Include one invalid unit, duplicate SKU, malformed amount or wrong header; attempt posting. | Errors identify invalid rows; no subset of the batch is silently imported. |
| TOOL-10 | P1 / Browser | CSV encoding and size limits | Try invalid encoding, an empty file, incorrect columns, more than 1000 rows and a file over 2 MB. | The import is rejected or marked invalid under the documented limits, with no catalog mutation. |
| TOOL-11 | P0 / Browser | Import retry/drift/quota | Retry a posted batch; in another preview create a conflicting SKU or reduce available quota before posting. | A posted retry does not duplicate products; changed/conflicting/over-quota imports fail atomically. |
| TOOL-12 | P0 / Browser | Private export types | Export catalog, sales, customers and stock movements with appropriate source permissions. | Each export includes only permitted business rows/fields; the download requires authentication and resource permission. |
| TOOL-13 | P0 / Browser + fixture | Export ownership and expiry | Try another tenant's export URL, sign out before download, then test a controlled expired export and run cleanup. | Foreign/unauthenticated/expired downloads fail; cleanup removes only eligible private export files. |
| TOOL-14 | P1 / Browser | CSV formula escaping | Export a fake customer/product text beginning with =1+1 or another spreadsheet formula prefix. | The CSV prefixes the dangerous text so spreadsheet opening treats it as text. |
| TOOL-15 | P1 / Browser | Support workflow | Open a case with subject/description; inspect it from another business; resolve it through the authorized operator CLI. | Case visibility stays tenant-scoped; resolution is stored and audited without user impersonation. |
| TOOL-16 | P1 / Browser | Workspace permissions/status | Try each tools tab under restricted roles; inspect Stripe and latest operator-run status. | Only permitted tabs/APIs work; setup flags are not mistaken for proof of paid access or healthy background workers. |

### UX — Navigation, browser behavior and hardware acceptance

Prerequisites: Use the supported desktop browser and a second browser/mobile viewport. Physical scanner/printer cases require the actual hardware.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| UX-01 | P1 / Browser | Every sidebar and setup route | Open each permitted sidebar entry, supporting setup tab and direct URL; test Back/Forward and reload. | The intended page loads or shows a clear permission/setup state; no blank screen or unexpected 500 occurs. |
| UX-02 | P1 / Browser | No active business | Use an account with no active business context and open a business-scoped route. | The app directs the user to business selection instead of exposing another business. |
| UX-03 | P1 / Browser | Loading/error/empty states | Use an empty test tenant and simulate slow/offline API requests; retry. | Loading, empty records and errors are clear; retry does not duplicate a transaction. |
| UX-04 | P1 / Browser | Form error usability | Submit invalid values in product, POS, return, expense and settings forms. | Relevant field/server errors are visible; entered values can be corrected. |
| UX-05 | P1 / Browser | Search/pagination/lookups | Create more records than one page; search and select a record outside the first lookup page. | The correct stable record ID is selected; paging does not switch it to another record. |
| UX-06 | P1 / Hardware | Keyboard scanner | Using the actual scanner, scan valid/unknown barcodes and press its Enter suffix while editing quantities. | Scanner behavior is predictable and cannot trigger an unintended checkout. |
| UX-07 | P1 / Hardware | Receipt printer | Print a long multi-line and a returned/part-paid invoice on the intended receipt printer. | Invoice, original values, tax, payment/balance and footer remain legible; controls are excluded from print. |
| UX-08 | P2 / Browser | Mobile/responsive access | Use narrow widths to navigate menu, forms, tables, POS and billing. | Essential controls remain usable and totals/errors are readable. |
| UX-09 | P0 / Browser | HTML/script-like text | Store harmless script-looking text in a permitted fake name/note and render it in the React screens. | It is displayed as text rather than executed. |
| UX-10 | P2 / Browser | Copy and scope accuracy | Review Overview, product help text, reports, supported tracking and billing descriptions against implemented behavior. | Record known stale wording as a usability issue; do not count an old Planned label as evidence that working modules are absent. |

### RACE — Concurrent requests and network retries

Prerequisites: Developer/operator tests only on a disposable MariaDB restore. Use two independent processes or browsers sharing a synchronized start; ordinary sequential double-clicking does not prove concurrency.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| RACE-01 | P0 / Race | Final-unit checkout race | Make one unit available; start two different checkouts for it at the same instant. | Exactly one sale succeeds; stock is zero and never negative; the losing sale/payment rolls back. |
| RACE-02 | P0 / Race | Identical checkout/receipt race | Send identical same-key checkout requests together; separately race identical purchase receipt requests. | Each pair returns one logical result with one financial/stock posting. |
| RACE-03 | P0 / Race | Competing return requests | Approve two different returns for the same final sold unit; post concurrently. | One posts and the other conflicts; stock and returned quantity increase once. |
| RACE-04 | P0 / Race | Refund duplication/overrefund race | Race identical refund requests, then distinct requests against one remaining refundable amount. | Same-key requests resolve once; distinct keys cannot exceed the collected entitlement. |
| RACE-05 | P0 / Race | Return versus cancellation | Race posting of a return against full cancellation on the same sale. | Only one can restock the units; aggregate credits remain reconciled. |
| RACE-06 | P0 / Race | Shift close versus posting | Race shift close against a cash refund/collection/expense posting. | A coherent serialized result occurs: posting is included before close or rejected after it; stored closing expected equals final allowed shift activity. |
| RACE-07 | P0 / Race | Concurrent plan limits | At one remaining product/staff/store slot start two eligible creation/reactivation/import requests. | Only allowed capacity commits; the tenant cannot exceed its limit. |
| RACE-08 | P0 / Race + fixture | Overlapping webhook synchronization | Run duplicate and different verified events concurrently; delay one authoritative fetch so another generation commits first. | The slower stale result cannot overwrite newer reconciliation; invoice/payment/access effects are not duplicated. |

### OPS — Automated regression, monitoring and recovery

Prerequisites: Run tests with the existing isolated test fixtures. Operational recovery checks require a disposable local environment and the supported backup driver; do not stop shared services or restore over the source.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| OPS-01 | P0 / CLI | Backend automated suite | From POS backend run php artisan test --compact --do-not-cache-result. | The isolated suite passes; failures and exact counts are recorded without running historical migrate:fresh/seeders. |
| OPS-02 | P0 / CLI | React regression/build | From POS Frontend run npm.cmd test, then npm.cmd run build. | Tests/build succeed; this does not replace actual browser/hardware acceptance. |
| OPS-03 | P1 / API + Ops | Readiness success/failure | Request /api/v1/health/readiness; simulate only the UAT connection/cache failure in an isolated fixture and retry. | A working dependency check returns 200/ready true; failure returns 503 without connection secrets. |
| OPS-04 | P0 / Ops | Queue and scheduler operation | Start the configured worker/scheduler; receive a test event, expire an export and inspect maintenance/reconciliation execution. | Background processing and cleanup run as configured; a 200 readiness response alone is not treated as worker proof. |
| OPS-05 | P0 / Ops + fixture | Failed event recovery | Cause a verified test event to exhaust retries; inspect sanitized failure evidence, fix configuration and use billing:reconcile --retry-event. | The stored verified event can recover once; an arbitrary unsigned event cannot be promoted to payment. |
| OPS-06 | P0 / Ops | Encrypted backup | Run the supported local operations:backup and inspect private encrypted artifacts/recorded status. | An encrypted database/backend/config backup is recorded; no secret or plain dump is published. |
| OPS-07 | P0 / Ops | Isolated restore drill | Run operations:backup --restore-drill with source/staging targets independently checked. | A new isolated database restores; captured schema/counts/fingerprints and file checksums reconcile; source is untouched. |
| OPS-08 | P0 / Ops | Corrupted backup detection | Use a copied test backup with a deliberately corrupted encrypted artifact or checksum and try isolated verification. | Verification fails rather than reporting success or overwriting source; evidence is preserved. |
| OPS-09 | P0 / Ops + staging | Off-host recovery and frontend artifact | In deployment rehearsal recover from the independent retained backup/key procedure and restore the matching frontend release artifact. | Recovery succeeds without relying only on this machine/Windows account; frontend and backend versions match. |
| OPS-10 | P0 / Review | Dependency and deployment review | Review fresh audits, legacy missing-route/controller findings, HTTPS origins/cookies, debug setting, public document root and file permissions. | Unresolved applicable vulnerabilities, exposed private files or unverified deployment assumptions block production approval. |

### CUT — Phase 8 populated-data rehearsal and launch acceptance

Prerequisites: Acceptance targets for the pending Phase 8, not claims of completed work. Requires a populated approved backup, reviewed migration policy, staging and business-owner signoff.

| ID | Priority / method | Scenario | Steps | Expected result |
| --- | --- | --- | --- | --- |
| CUT-01 | P0 / Review + Ops | Populated baseline | Capture source IDs/counts, stock, sales/discount/tax/returns, collections/due figures, dealer/daily-account evidence and unresolved issues. | A reproducible baseline exists; empty local tables or synthetic fixtures do not count as populated production acceptance. |
| CUT-02 | P0 / Ops + staging | Historical rehearsal and retry | On the restored staging copy run the reviewed repeatable migration/mapping process twice. If the required historical importer/policy is not implemented, record Blocked. | No duplicate historical transactions/openings occur; original values/unknown evidence are preserved and target counts reconcile. |
| CUT-03 | P0 / Review | Reconciliation exceptions | Compare source/target totals and review every unresolved/ambiguous historical record. | Blocking discrepancies are resolved or explicitly retained under an owner-approved policy before launch; no silent guessing or deletion. |
| CUT-04 | P0 / Browser + Review | Role/workflow UAT signoff | Business representatives run real browser workflows for owner, cashier, stock staff and accountant, including scanner/printer. | Expected outcomes and evidence are signed off; no critical financial/isolation failure remains open. |
| CUT-05 | P0 / Ops + staging | Maintenance and final backup | Rehearse the approved maintenance window, old-writer stop and final consistent database/files/config/frontend backup. | No untracked legacy writer can continue during final import; the recovery checkpoint is verified. |
| CUT-06 | P0 / Ops + staging | Final incremental import and switch | Rehearse the final delta import, reconciliation and controlled routing to React while retaining legacy read-only access. | There is one authorized stock-writing path and no missing/duplicated transaction interval. |
| CUT-07 | P0 / Ops + staging | Rollback preserving new sales | In staging create new transactions after simulated switch, trigger the rollback procedure and reconcile those transactions. | Rollback preserves all post-switch sales/payments/stock changes; an old backup is not restored over new trading activity. |
| CUT-08 | P0 / Review | Launch go/no-go and monitoring | Review test results, unresolved issues, recoverability, queues/backups/alerts, final plan terms and separate live-billing approval. | Named approvers record Go/No-go; absent approval or a blocking failure means no production cutover/live charging. |

## 7. Recording defects and regression

For a failed case, record its ID and business/role/environment, exact reproducible steps and fixture state, expected versus actual, relevant amounts/stock before and after, request ID, severity and private evidence location. Do not record secrets or infer that a repeated request caused a duplicate without comparing saved IDs/movements/payments.

After a fix, rerun the failed case and related money/stock/access/retry cases. Run the existing automated suite for application changes. A text-only checklist change does not itself require application regression.

```powershell
# POS backend (existing isolated tests)
php artisan test --compact --do-not-cache-result

# POS Frontend
npm.cmd test
npm.cmd run build
```

Existing automated coverage is in backend tests/Feature (ApiFoundation, TenancyCatalog, LegacyCatalog, LegacyCutover, InventoryPos, ReturnsCash, StripeBilling and WorkspaceTools) and frontend src/test. Some foundation test names still describe their original phase; validate current behavior using the full suite and current API contracts. MariaDB concurrency helpers in scripts verify stock/financial races on the checked isolated restore; they must not be repointed to source. This document does not execute those scripts.

## 8. Acceptance and pending resources

- All applicable P0 tests must pass with evidence before launch; no unresolved money/stock/isolation/preservation failure may be accepted as a cosmetic issue.
- P1/P2 failures require recorded triage and explicit business acceptance where a release proceeds. Record actual scanner/printer and role-based business UAT.
- If a case requires unconfigured Stripe, HTTPS staging, populated historical data, hardware or off-host recovery, mark it Blocked with the missing prerequisite. Phase 8 and live charging remain pending until their separate requirements and approvals are met.
- Do not claim a successful real Stripe lifecycle from a fake gateway/signature unit test, full historical migration from empty-source counts, or off-host recoverability from only this Windows DPAPI backup.
- An order/customer/ledger conversion with unknown legacy meaning needs the documented owner review policy. Preserve unknown refunds and unresolved exceptions rather than fabricating entries.
- Overview/product screens have known old Planned/help wording. Record copy issues separately from functional tests; this checklist has not changed those screens.
- A stock transfer, offline POS, operational batch/serial tracking or full double-entry accounting is outside current Phase 0–7 scope. Confirm its explicit unsupported behavior or mark the feature request outside scope; do not invent successful tests for an absent module.
- Source credentials, existing APP_KEY and business history must remain preserved. Live subscriptions/charges require explicit separate production approval.

The eight required Stripe event types are checkout.session.completed, checkout.session.async_payment_succeeded, checkout.session.async_payment_failed, customer.subscription.created, customer.subscription.updated, customer.subscription.deleted, invoice.paid and invoice.payment_failed. Test against authoritative subscription state with a worker, not only redirect/event payload status.

Further references: [project overview/setup](IMPLEMENTATION.md), [inventory/POS guide](PHASE_4_5.md), [returns/operations guide](PHASE_6_7.md), [Stripe configuration](STRIPE_BILLING.md), [HTTPS staging](STAGING.md), [recovery](RECOVERY.md), and [API contracts](api-contracts/phase-6-7.md).

## 9. UX-5 accessibility/performance follow-up

The user approved UX-5 accessibility/performance refinements on 2026-11-05. [UX5_REFINEMENT.md](UX5_REFINEMENT.md) documents the changes, automated evidence and manual release checks. Cases **UX5-01–UX5-12** in [UAT_TEST_CASES.csv](UAT_TEST_CASES.csv) cover page titles/focus, asynchronous forms, focus preservation, modal disclosures, screen-reader pagination, reduced motion, zoom/touch, lazy route loading, dashboard no-op/refresh behavior, cancellation, access isolation and checkout retries.

These added UAT rows remain **Not Run** for human/device acceptance. Automated synthetic browser/DOM checks do not stand in for a native NVDA/JAWS/VoiceOver session, physical scanner use, actual device zoom/touch testing or production-network validation. Keep the earlier P0 business-data and release prerequisites in force.

