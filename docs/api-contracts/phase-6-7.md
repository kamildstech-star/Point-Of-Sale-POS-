# Finance, Stripe billing and workspace APIs

Base `/api/v1`. Existing session/Sanctum CSRF authentication and active server-resolved tenant context apply, except the two public endpoints below. No request body/query tenant ID selects the account. Authorization is checked in Laravel. Decimal quantities/amounts are strings. Mutating financial requests require `idempotency_key`, except state transitions which safely repeat their existing state. Single records use `{data: ...}` and lists use Laravel pagination.

| Method / path | Purpose / permission |
| --- | --- |
| GET `returns`, `returns/{id}` | Return documents and original sale financial balances; `sales.view` |
| POST `sales/{id}/returns` | `{reason, items:[{sale_item_id,qty,disposition}]}` plus key; disposition resellable/damaged; `sales.return` |
| POST `returns/{id}/approve`, `returns/{id}/post` | Approval then inventory/credit posting; `sales.return.approve` |
| POST `sales/{id}/void` | Full approved return `{reason}` plus key; `sales.void` + approval |
| POST `returns/{id}/refunds` | `{cash_session_id,method_id,amount,reference?}` plus key; `sales.refund` |
| POST `returns/{id}/exchange` | `{quote_id,payments:[{method_id,amount,reference?}]}` plus key; approve/refund/create/payment permissions |
| POST `pos/quotes` | Existing quote contract additionally accepts `exchange_return_id`; server returns available credit/amount to collect |
| GET `cash/sessions?history=1` | Existing shift list optionally includes closed shifts |
| GET `cash/sessions/{id}/reconciliation` | Methods, expected cash, refunds/movements/expenses and shift state |
| POST `cash/sessions/{id}/movements` | `{movement_type:in/out,amount,reason}` plus key; `cash.move` |
| POST `cash/sessions/{id}/close` | `{counted_cash}` plus key; stores expected/variance/snapshot; `cash.close` |
| GET/POST `expenses/categories` | List/create `{name}`; expenses.view/manage |
| GET/POST `expenses` | Draft `{category_id,branch_id,method_id,cash_session_id?,amount,description,expense_date,reference?}` plus key |
| POST `expenses/{id}/approve` | Post financial effect; `expenses.approve` |
| GET `reports/summary?from=YYYY-MM-DD&to=YYYY-MM-DD&branch_id=...` | Inclusive tenant-local dates, max 366-day difference; `reports.view` |
| POST `legacy/financial-reconciliation` | Source snapshots and review issues, unknown refund history; `legacy.manage` |
| GET `billing/plans` | Public active provisional plans/prices/limits, no Stripe secret/Price IDs; owner/billing.manage |
| GET `billing/subscription` | Current tenant subscription, access policy, pending session |
| POST `billing/checkout` | `{plan_price_id,idempotency_key}` → `{data:{id,url}}` hosted Checkout |
| POST `billing/checkout/{id}/expire` | Scoped known session expiration; uncertain in-flight results require reconciliation |
| POST `billing/portal` | `{}` → `{data:{url}}` Stripe Customer Portal |
| GET `billing/invoices`, `billing/usage` | Tenant invoices / authoritative resource counts |
| GET/PATCH `workspace/settings` | Receipt fields, return_days nullable, low_stock_threshold nullable; settings.manage |
| GET `workspace/audit`, `workspace/alerts` | Separate audit.view / alerts.view permissions |
| POST `workspace/alerts/{id}/read` | Mark alert read |
| GET `workspace/templates`, PUT/PATCH `workspace/templates/{code}` | Literal `{body_template,active}`; settings.manage; stock.low/billing.review |
| GET/POST `workspace/imports` | Multipart `file` → validated batch; imports.manage + products.manage |
| POST `workspace/imports/{id}/post` | Post validated batch once, with quotas rechecked |
| GET/POST `workspace/exports` | `{type:catalog/sales/customers/stock-movements}`; exports.manage plus resource permission |
| GET `workspace/exports/{id}/download` | Private authorized CSV, 24-hour expiry |
| GET/POST `workspace/support` | `{subject,description}`; support.manage |
| GET `workspace/operations` | Sanitized configuration and latest operator status; settings.manage |
| POST `webhooks/stripe` | **Public session-independent endpoint**; raw body and `Stripe-Signature` verification; queued 202 |
| GET `health/readiness` | **Public** minimal DB/cache availability, 200/503 |

Subscription access/plan module gates apply in addition to permissions. Read-only tenants retain permitted records, billing, exports and support. API responses/logs include `X-Request-ID`. Webhook signature errors return 400; missing setup 503; conflicts/limits 409; validation 422; unauthorized/missing scoped records 403/404. Public webhooks and expensive bulk/Checkout/Portal operations are rate limited. See [Stripe setup](../STRIPE_BILLING.md) for event processing, retries and real-account validation still required.
