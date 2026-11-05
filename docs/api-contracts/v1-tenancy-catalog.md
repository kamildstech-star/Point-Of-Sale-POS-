# Tenancy and catalog API

Session-only Sanctum authentication and CSRF rules continue from phase 1. `/api/v1/auth/me` now returns the validated `active_tenant`, tenant role and stable permission codes. Multiple memberships require an explicit selection. A customer row without staff membership remains ineligible to sign in.

| Method | Path under `/api/v1` | Behavior |
| --- | --- | --- |
| POST | `auth/register` | New owner identity and business in one transaction; password confirmation required |
| GET / POST | `tenants` | List own active memberships / create a business and owner membership |
| POST | `tenants/{id}/activate` | Verify membership, establish session context, rotate session/CSRF |
| GET | `context` | Active business, permissions, branches and locations |
| GET / POST | `team` | Scoped staff list / new staff account or access grant to an existing staff identity |
| PATCH | `team/{membership}` | Change tenant role/status; preserve last active owner |
| GET / POST | `roles` | Scoped roles and permission codes / create custom role |
| PATCH | `roles/{role}` | Edit a custom role; system roles are immutable |
| GET / POST | `products` | Paginated search / create product with required first variant |
| GET / PATCH / DELETE | `products/{product}` | Scoped detail/update/archive; DELETE retains history |
| POST / PATCH | `products/{product}/variants[/{variant}]` | Add variable-product variant / update its values |
| GET | `variants[/{variant}]` | Scoped paginated SKU lookup / variant detail |
| GET | `products/export` | Scoped CSV download |
| POST | `products/{product}/images` | Private multipart `image` upload |
| GET | `catalog-images/{image}` | Authorized private file download |
| GET / POST | `catalog/{resource}` | Scoped paginated references / create |
| GET / PATCH / DELETE | `catalog/{resource}/{id}` | Scoped detail/update/archive supported references |
| GET | `legacy/catalog` | Legacy stock/mapping/exception pages and reconciliation batch |
| POST | `legacy/sku-policy` | Owner-only explicit `distinct_by_school` approval with `confirmed=true` |
| POST | `legacy/catalog-map` | Reconcile to a verified `location_id`; no stock postings |

All business endpoints require an active membership and tenant context. Foreign record IDs return 404. No active business returns 409. Missing permission/suspended mutations return 403. Invalid parents, decimals, rate periods or fields return 422; concurrent uniqueness conflicts return 409. Input `tenant_id` is not trusted or copied into writes. Lookup/reference lists support `search` and `page`; product lists have 25 rows per page, reference/variant lookups 50. Team lists are scoped; they never expose passwords. Legacy reports use `page`, `mapping_page`, `exception_page`.

Reference resources are `categories`, `brands`, `units`, `attributes`, `attribute-values`, `variant-values`, `barcodes`, `tax-classes`, `tax-rates`, `price-lists`, `price-list-items`, `branches`, `locations`. Archive retains categories/brands/units/attributes/tax classes/price lists; other reference rows are retained and edited rather than deleted. Products/variants have `active` state. SKU input is normalized to uppercase. Barcodes preserve source text; the database's tenant-scoped uniqueness/collation applies.

Product creation example:

```json
{"name":"Cotton shirt","product_type":"simple","track_inventory":true,"variant":{"sku":"SHIRT-001","base_unit_id":1,"default_cost":"100.2500","default_price":"125.5000","tracking_mode":"none","active":true}}
```

`category_id`, `brand_id`, `tax_class_id`, description and active state are optional. Product types are simple, variable, service, digital; bundle recipes are deferred. A variant has a unit, SKU, optional name, nonnegative cost/price (up to four decimal places), tracking mode none/batch/serial and active state. Tracking configuration does not implement stock, batch or serial posting yet. Price-list amounts and tax rates are configuration; checkout authority arrives later.

Read permission is `products.view`; catalog writes require `products.manage`. Branch/location writes require `settings.manage`. Team reads/writes require `team.view`/`team.manage`; only an owner may alter owner membership. Legacy reads/writes require `legacy.view`/`legacy.manage`, with owner-only SKU policy approval. No platform superuser bypass exists. Shared staff profile/password changes are not part of tenant team mutations.
