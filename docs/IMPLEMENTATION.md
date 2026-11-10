# Phases 0 and 1 implementation

Latest dashboard update (2026-11-06): [DASHBOARD_D5_D6_D7.md](DASHBOARD_D5_D6_D7.md) documents the implemented specialist/personal/platform projections, backend support audit, local verification and pilot/rollback controls. D7 staging/device acceptance, pilot feedback and human sign-off remain pending. The earlier phase notes below describe their original scope.

The professional UI refinement track has completed its UX-0 audit and implements UX-1 dashboard hierarchy/grouped navigation. See [UX1_REFINEMENT.md](UX1_REFINEMENT.md) for the reference-based sidebar, role/scope preservation, screenshots and phase review boundary.

The React workspace now follows the Compact Enterprise sizing guide: compact management pages and a separate comfortable checkout profile. See [COMPACT_UI.md](COMPACT_UI.md) for the audit, shared components, sizing comparison, screenshots and verification. This UI refinement does not advance the separate dashboard D5–D7 phases.

The React workspace now uses a central non-blocking request loader and shared contextual skeleton/refresh states. See [GLOBAL_LOADING.md](GLOBAL_LOADING.md) for behavior, coverage, configuration, tests and previews.

The separate UI design track now has its UI-0 audit and UI-1 light/dark foundations. See [UI_FOUNDATIONS.md](UI_FOUNDATIONS.md) for the theme toggle, component foundations, preview screenshots and remaining UI phases, and [UI_FOUNDATIONS_AUDIT.md](UI_FOUNDATIONS_AUDIT.md) for the route migration matrix. These UI phase numbers are separate from the backend blueprint phases below.

Dashboard D1 adds the shared responsive shell, collapsible desktop navigation, a focus-managed tablet/mobile drawer, real business switching, permitted-page search and reusable dashboard components. See [DASHBOARD_D1.md](DASHBOARD_D1.md).

Dashboard D2 supplies server-resolved access context, explicit branch/personal scopes, additional-role unions and authorized cached dashboard APIs. See [DASHBOARD_D2.md](DASHBOARD_D2.md) for routes, financial definitions and access administration. Existing access is preserved unless explicitly restricted.

Dashboard D3 connects the business owner/admin Overview screens to those APIs, with scoped KPIs, sales/branch charts, product rankings, stock alerts, approvals, quick actions and functioning report/document filters. See [DASHBOARD_D3.md](DASHBOARD_D3.md).

Dashboard D4 adds the cashier and branch-manager Overview profiles, always-personal cashier metrics, branch staff activity and a real versioned Hold/Recall cart workflow with fresh quoting and atomic checkout. See [DASHBOARD_D4.md](DASHBOARD_D4.md). Existing staff access is preserved; explicit branch/personal restrictions remain enforced. Subsequent D5–D7 engineering is documented above.

The React sidebar fits its menus, stays pinned during desktop page scrolling and provides internal overflow for long menus. Mobile/tablet now use the D1 drawer. See [SIDEBAR_LAYOUT.md](SIDEBAR_LAYOUT.md).

Remix Icon now supplies the application icons in React and the legacy Blade interface. See [REMIX_ICONS.md](REMIX_ICONS.md) for coverage, reusable components and local asset generation.

Editable records now have pencil/trash actions, shared prefilled popup editors, confirmation dialogs and tenant-protected removal. See [RECORD_ACTIONS.md](RECORD_ACTIONS.md) for supported records, history retention and acceptance checks.

Global React Toastify notifications now report action successes, failures and warnings throughout the React workspace. See [GLOBAL_NOTIFICATIONS.md](GLOBAL_NOTIFICATIONS.md) for behavior and configuration.

React forms now use JavaScript validation with inline field errors alongside Laravel request validation. See [FORM_VALIDATION.md](FORM_VALIDATION.md) for coverage, rules and regression/manual checks.

Add/edit/action forms now open in popup modals. See [POPUP_FORMS.md](POPUP_FORMS.md) for the affected screens, interaction behavior and verification.

For manual acceptance testing of the current workspace, use [UAT_TEST_PLAN.md](UAT_TEST_PLAN.md) and [UAT_TEST_CASES.csv](UAT_TEST_CASES.csv): 257 cases across 20 areas, including exact financial checkpoints and pending Phase 8 gates. All manual result rows start as Not Run.

> Phase 6 and 7 implementation adds returns, refunds, exchanges, cash closing, reports, Stripe subscriptions and workspace operations. Read [PHASE_6_7.md](PHASE_6_7.md) and [STRIPE_BILLING.md](STRIPE_BILLING.md). Existing data/access are preserved; Stripe charging defaults off. Older milestone descriptions below are historical.

> Phases 4 and 5 are now implemented and applied locally. Use the development-server commands below, then read [PHASE_4_5.md](PHASE_4_5.md) for inventory, purchasing, branch activation, POS, customers, payments and receipts. Composer now explicitly requires BCMath. Sign in again to refresh permissions. The original phase 1 feature/verification descriptions below are historical.

> Phases 2 and 3 are now implemented and applied locally. Read [PHASE_2_3.md](PHASE_2_3.md) for tenant onboarding, catalog setup, approved legacy mapping, migration evidence and current limitations. The phase 1 tenant-placeholder descriptions below are historical.

The repository keeps the user's layout: `POS backend` is the existing Laravel application and `POS Frontend` is the React JavaScript application. The business schema is unchanged. The original system guide was preserved as [AGENT.md](AGENT.md); the blueprint remains the roadmap.

From separate PowerShell terminals:

```powershell
Set-Location 'POS backend'
composer install
php artisan serve --host=127.0.0.1 --port=8000
```

```powershell
Set-Location 'POS Frontend'
npm.cmd ci
npm.cmd run dev
```

Open **http://127.0.0.1:5173** and use an existing staff email/password. Development proxies `/api` and `/sanctum` to Laravel, so no `.env` edits are necessary. Do not open `index.html` directly. There is no public registration or fabricated demo user. Role-2 staff without dashboard permission can use Business context; the overview correctly shows an access restriction.

The API implements session login/logout, a safe current-user resource, explicit errors/validation, rate limits and a non-mutating tenant placeholder. The frontend implements React Router navigation, login, overview, business context, permissions-aware navigation and error/retry states. It stores authentication in session cookies, not localStorage. Business modules are explicitly planned, not simulated with invented figures.

Verification:

```powershell
Set-Location 'POS backend'
php artisan test --no-ansi --do-not-cache-result
php artisan legacy:audit --output='../docs/audit/private/current.json'
```

```powershell
Set-Location 'POS Frontend'
npm.cmd test
npm.cmd run build
```

PHPUnit forcibly uses SQLite `:memory:` and a test-only application key. Feature fixtures are created only in that isolated connection; they do not run the historical migration chain. Tests cover staff/customer boundaries, safe serialization, session/CSRF rotation, CSRF enforcement, CORS, validation, login throttling and tenant-switch refusal. React tests mock API calls and cover login/logout, route restoration, permission denial, tenant placeholder, errors/retry and CSRF request order. A browser/HTTPS staging walkthrough remains a deployment acceptance step.

The read-only audit inventories live tables, indexes, foreign keys, DDL, migration status, routes, orphans, duplicate stock identities/barcodes, negative balances and reconciliation fingerprints. It runs in a read-only transaction; private artifacts are ignored by Git. Missing legacy controllers/methods remain documented rather than being replaced with speculative business implementations. Consequently `route:list`, even filtered, still encounters legacy reflection failures; use the audit's route inventory for the new API.

Local results (2026-10-19): **14 backend tests passed, 98 assertions; 9 frontend tests passed; Vite production build passed; no TypeScript source files.** Encrypted backup restore matched table DDL/counts and business/financial fingerprints and verified 2,851 archived file checksums. Source business fingerprints and the original `.env` checksum were unchanged. The local schema and sanitized inventory are in [audit/local-schema.md](audit/local-schema.md) and [audit/local-audit.md](audit/local-audit.md). The user has confirmed that no staging server is available yet; HTTPS deployment remains pending.

Composer was restored from the original lockfile and only Sanctum was added; Laravel remains 11.8.0. Composer reported 59 security advisories across 16 locked packages. A compatible security-upgrade pass is required before public deployment; this change does not silently update the legacy dependency graph. Existing class/file case mismatches also remain in legacy code.

See [API contract](api-contracts/v1-foundation.md), [engine decision](decisions/ADR-001-db-engine.md), [legacy mapping CSV](migration-mappings/legacy-to-target.csv), [recovery procedure](RECOVERY.md), and [staging configuration](STAGING.md). Production backup reconciliation/operator sign-off and actual HTTPS staging deployment require the deployment environment; local implementation alone does not satisfy those acceptance gates.
