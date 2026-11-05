# Version 1 foundation API

> Phase 2 replaces the empty tenant list/switch placeholder and adds owner registration. See [v1-tenancy-catalog.md](v1-tenancy-catalog.md) for current membership, activation, context and catalog contracts; the phase 1 placeholder responses below are historical.

Base: `/api/v1`. All responses are JSON, including errors without an `Accept` header. Send `Accept: application/json` and credentials with every frontend request.

| Method | Path | Authentication | Result |
| --- | --- | --- | --- |
| GET | `/sanctum/csrf-cookie` (outside API prefix) | Public | 204; initializes session and XSRF cookie |
| POST | `/api/v1/auth/login` | CSRF-protected | 200 staff resource; email/password required |
| GET | `/api/v1/auth/me` (alias `/api/v1/me`) | Staff session | 200 staff resource |
| POST | `/api/v1/auth/logout` | Any authenticated session + CSRF | 204; invalidates session, rotates CSRF token |
| GET | `/api/v1/tenants` | Staff session | 200 empty membership list and onboarding metadata |
| POST | `/api/v1/tenants/{tenant}/activate` (alias `/api/v1/tenant/switch`) | Staff session + CSRF | 409 until verified memberships exist in phase 2 |

Staff response:

```json
{"data":{"id":1,"name":"Example Staff","email":"staff@example.test","role":{"id":1,"name":"Super Admin"},"permissions":["dashboard.view"],"active_tenant":null}}
```

The serializer explicitly selects fields. Passwords, remember tokens and raw numeric permission arrays are never returned. The phase 1 adapter maps only legacy route permission 13 to `dashboard.view`. Invalid/null legacy role permission JSON yields no permissions. Roles 1 and 2 with an existing role record may authenticate, matching the legacy staff boundary; customer rows cannot. New tenant RBAC and branch restrictions belong to phase 2.

Tenant response:

```json
{"data":[],"meta":{"active_tenant":null,"onboarding_available":false}}
```

No tenant ID is accepted or persisted. No stock, sales, reporting or customer API is exposed in this phase.

Errors have `message`; 422 validation responses also have `errors` keyed by field. Statuses: 401 missing session, 403 non-staff session, 404 missing resource, 409 onboarding unavailable, 419 missing/expired CSRF, 422 invalid fields/credentials, 429 rate limit, 500 generic unexpected error. Exception internals are not returned. Rate-limit responses retain `Retry-After`. Login allows 5 requests per normalized email/IP per minute plus 30 per IP; authenticated endpoints allow 120 per user/IP per minute. Use a shared cache store across API replicas.

Authentication uses Sanctum's CSRF initialization and `auth:sanctum`, backed by Laravel's `web` session guard. Bearer-token retrieval is explicitly disabled for this session-only release, so arbitrary bearer strings return 401 without querying a token table. These endpoints explicitly apply the `web` middleware and exclude Sanctum's additional stateful middleware to avoid running cookie decryption/session/CSRF twice. `statefulApi()` remains configured for future API routes. Even a request without Origin/Referer must pass CSRF on mutations. No bearer-token model, personal token table, token issuance, or localStorage credentials are needed.

Axios uses `withCredentials` and `withXSRFToken`; it initializes CSRF before login and logout. Login rotates the session ID and CSRF token. CORS accepts only exact configured origins with credentials; it never uses a wildcard. CORS is browser response access control; CSRF protects mutations. Prefer a same-origin reverse proxy. For intentional sibling subdomains, configure the session domain, stateful host/ports and exact allowed origins consistently, and verify cookies under HTTPS.

Implementation follows the [Laravel 11 Sanctum SPA authentication guidance](https://laravel.com/docs/11.x/sanctum#spa-authentication), with the explicit session-only route boundary described above.
