# Stripe subscription billing

Phase 7 uses Stripe-hosted Checkout (`mode=subscription`) and Stripe Customer Portal, with the official `stripe/stripe-php` SDK. Retail customer card/bank entries remain records of confirmed external POS payments. They do not charge cards through this SaaS billing integration.

Charging is disabled by default. No Stripe customer, subscription or charge is created by migrations, plan seeders or the installer. Existing tenants receive preserved local access; tenants created after installation require a verified subscription. Prices and limits are provisional and require business approval before production.

## Architecture and compatibility decisions

- A tenant owns the subscription and Stripe Customer. Staff identities can belong to several tenants, so user-owned Cashier subscriptions would bind billing to the wrong identity. The official SDK integrates with the existing tenant context and transaction conventions.
- Existing `sale_payments`, `purchase_settlements` and retail `refunds` retain their financial meaning. SaaS invoices use `saas_invoices` and `saas_invoice_payments`, separately from sales and legacy orders. `tenant_subscriptions`, `subscription_items`, `billing_webhook_events`, `billing_checkout_attempts` and `tenant_usage` hold billing state.
- Session authentication and the selected active membership determine the tenant. A client-supplied tenant ID never selects the billing account. Owner or `billing.manage` permission is required. Webhooks use a separate raw-body Stripe signature instead of session authentication/CSRF.
- Subscription writes and quotas lock the tenant row, as Phase 4/5 postings do. Stripe HTTP requests happen outside that lock. Webhook processing fetches authoritative Stripe state and checks a generation before committing, so a slower reconciliation cannot overwrite a newer one.
- The SDK supports current subscription item period fields and older top-level period fields. Pin the installed lockfile and test a Stripe API version change before deployment.

## Test configuration

Keep the existing backend `.env` and `APP_KEY`. Add test credentials privately using the variable names in `.env.example`; never put secrets in React, source control, test fixtures or screenshots. Example non-secret settings:

```dotenv
STRIPE_BILLING_ENABLED=true
STRIPE_LIVE_ENABLED=false
STRIPE_CURRENCY=usd
FRONTEND_URL=http://127.0.0.1:3002
BILLING_QUEUE_CONNECTION=database
DB_QUEUE_RETRY_AFTER=180
BILLING_LEGACY_ACCESS=true
BILLING_GRACE_DAYS=7
```

Set `STRIPE_KEY` to the test publishable key, `STRIPE_SECRET` to a test secret/restricted key, and `STRIPE_WEBHOOK_SECRET` to the endpoint's signing secret. This hosted redirect flow does not need Stripe.js or a publishable key in React. `FRONTEND_URL` must exactly match the browser origin used for this installation; use port 5173 instead if running Vite there. The backend rejects live secret keys/events unless the separately gated live setting is enabled. Production approval has not been given.

From `POS backend`, clear cached configuration after changing environment settings, then run the API and worker in separate terminals:

```powershell
php artisan config:clear
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
php artisan queue:work database --tries=5 --timeout=90
```

Use queue visibility/retry-after greater than the job timeout (database default 180 seconds). A production process supervisor and scheduler are required. Stripe HTTP requests have bounded timeouts and one automatic network retry; failed jobs retain sanitized error codes and retry up to five times. Do not run billing on the `sync` queue in production.

Install/sign in to Stripe CLI in your own test account, then forward real test-account events:

```powershell
stripe listen --forward-to http://127.0.0.1:8000/api/v1/webhooks/stripe
```

Put the CLI-provided signing secret in `STRIPE_WEBHOOK_SECRET` locally. A deployed Dashboard webhook has its own secret. Subscribe to:

- `checkout.session.completed`
- `checkout.session.async_payment_succeeded`
- `checkout.session.async_payment_failed`
- `customer.subscription.created`, `customer.subscription.updated`, `customer.subscription.deleted`
- `invoice.paid`, `invoice.payment_failed`

The queue must run to activate subscriptions. Visiting `/billing/success` only polls status; it grants no access. Generic CLI fixture subscriptions are intentionally rejected when they do not belong to a registered customer and configured price. Test a subscription created through this app's Checkout for the complete lifecycle.

## Configure plans and real test Price IDs

The idempotent installer seeds these proposed USD terms, with no Stripe IDs and Checkout disabled:

| Plan | Monthly | Yearly | Stores | Active staff | Products | Imports |
| --- | ---: | ---: | ---: | ---: | ---: | --- |
| Starter | 19 | 190 | 1 | 3 | 1,000 | No |
| Professional | 49 | 490 | 3 | 10 | 10,000 | Yes |
| Business | 99 | 990 | 10 | 50 | 100,000 | Yes |
| Enterprise | Custom | Custom | Unlimited proposed | Unlimited proposed | Unlimited proposed | Yes |

Purchasing, returns and reports are enabled in these proposed plans. Products count catalog product records, not SKU variants; staff count active memberships including the owner; stores count branch records. Reducing a limit preserves existing records and blocks additional resources. Module and capacity checks run in Laravel.

Create matching recurring flat-rate Prices in Stripe **test mode**, USD, quantity one, month/year interval count one. Copy the actual IDs from your Dashboard. Configure a price without enabling it first, or explicitly enable that test price after review:

```powershell
# Replace the final argument with the actual test Price ID; this is a placeholder.
php artisan billing:configure-price starter-monthly price_REPLACE_WITH_ACTUAL_TEST_ID --enable-checkout
```

The command and Checkout verify Stripe's active flag, currency, amount and recurring interval. Never invent IDs or embed them in React. Rebinding a price used by subscriptions needs controlled reconciliation. Changing an existing local price's terms requires a new versioned price code. Arbitrary currencies beyond the initial two-decimal USD plan setup need currency minor-unit validation before use.

An operator can configure reviewed plan terms/limits with `php artisan billing:configure-plan path/to/reviewed-plan.json`. For example:

```json
{
  "code": "starter",
  "name": "Starter (reviewed test terms)",
  "prices": [{"code": "starter-monthly-v2", "currency_code": "USD", "interval": "monthly", "amount": "19.00"}],
  "entitlements": [
    {"feature_code": "products", "enabled": true, "limit_value": 1000},
    {"feature_code": "staff", "enabled": true, "limit_value": 3},
    {"feature_code": "branches", "enabled": true, "limit_value": 1},
    {"feature_code": "purchasing", "enabled": true, "limit_value": null},
    {"feature_code": "returns", "enabled": true, "limit_value": null},
    {"feature_code": "reports", "enabled": true, "limit_value": null},
    {"feature_code": "imports", "enabled": false, "limit_value": null}
  ]
}
```

Entitlement changes apply to tenants on that plan. Review affected tenants before running the operator command. Re-running the seeder preserves operator configuration.

Configure Stripe Customer Portal in test Dashboard: allowed products/prices, payment-method updates, cancellation timing, upgrade/downgrade behavior and return URLs. Only include mapped local Prices; an unknown Price fails synchronization safely. Supported changes are initiated in Portal and take effect locally after authoritative verification, including successful payment when required. Unpaid upgrades retain the previously verified plan/access period.

## Access, reconciliation and recovery

Verified active subscriptions and valid trials permit writes. Past-due access has a configurable seven-day grace period, bounded by the known period. Scheduled cancellation stops access at its timestamp. Unpaid, incomplete, paused, canceled and expired access are read-only. The owner can still reach billing, permitted exports and support; tenant data is retained. The existing Blade writers also respect read-only access. Administrative tenant status is a separate gate.

`BILLING_LEGACY_ACCESS=true` preserves pre-existing tenants until an explicit paid migration succeeds. Do not disable this globally without a reviewed migration plan. A failed initial Checkout does not remove grandfathered access. New tenants are not grandfathered automatically.

Checkout attempts use tenant-scoped request keys and an active slot. A known open session can be resumed/expired. An unknown/preparing result retains the slot so another request cannot create an unintended second subscription. Retry the same key; after 23 hours an operator must reconcile Stripe before releasing an unconfirmed attempt. Do not manually release a slot until the corresponding Checkout/subscription is resolved in Stripe. Stripe customer creation and Checkout creation have separate stable idempotency keys.

```powershell
php artisan billing:reconcile --tenant=TENANT_ID
php artisan billing:reconcile --retry-event=VERIFIED_EVENT_ROW_ID
php artisan operations:maintenance
php artisan schedule:work
```

Reconciliation cannot fabricate a paid subscription; it reads Stripe. Unknown prices, mismatched customer/tenant metadata, duplicate active subscriptions and network failures require operator review. Webhooks deduplicate by provider/event ID and payload hash, and synchronize inside transactions. No webhook payload status alone enables paid access. Stored raw events are private operational data, never returned by tenant APIs.

## Verification and deployment gate

Automated tests use a fake gateway and genuine SDK signature verification, never live keys. They cover Checkout/idempotency, roles, tenant isolation, signature failure, duplicate events, retry after failure, authoritative out-of-order events, activation, recurring failure/grace, cancellation, statuses, paid upgrades/downgrades and quotas. MariaDB process races also verify quota enforcement.

Actual Stripe Dashboard/CLI Checkout, Portal, asynchronous payment and recurring lifecycle tests remain pending because no test-account keys, configured Price IDs or staging HTTPS server were supplied. Before production, perform that complete test-account exercise, fix the dependency advisories in [PHASE_6_7.md](PHASE_6_7.md), configure HTTPS/session origins, queue supervision, monitoring and off-host recoverable backups, and obtain explicit approval for final terms and live charging.

Primary references: [Stripe webhooks](https://docs.stripe.com/webhooks), [subscription Checkout](https://docs.stripe.com/billing/subscriptions/build-subscriptions), [Customer Portal integration](https://docs.stripe.com/customer-management/integrate-customer-portal), [official PHP SDK](https://github.com/stripe/stripe-php).
