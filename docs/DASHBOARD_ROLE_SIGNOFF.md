# Dashboard D7 — Role acceptance matrix

Prepared 2026-11-05. Engineering evidence is linked from [D5–D7](DASHBOARD_D5_D6_D7.md) and the [verification manifest](audit/dashboard-d5-d7-verification.json). **No human sign-off has been supplied.** Automated fixture results do not sign this matrix on behalf of a user.

| Role / policy | Acceptance focus | Human status | Reviewer / date / evidence |
| --- | --- | --- | --- |
| Platform only | Explicit grant; aggregate DTO; no tenant calls; revoke and disabled pilot | Pending | |
| Platform plus separate tenant membership | Explicit workspace navigation; no automatic widening of tenant scope | Pending | |
| Owner | Existing overview/report reconciliation; permitted actions | Pending | |
| Business admin | Operational overview and approvals; no implicit platform/billing grant | Pending | |
| Branch manager, unrestricted | Select only permitted branches; real staff/receipt drilldowns | Pending | |
| Branch manager, assigned branches | Assigned branches only; no assignments returns no branch data | Pending | |
| Cashier | Fast New Sale/Hold/Recall; own shifts/receipts; scanner and safe retries | Pending | |
| Inventory | Unit-separated stock/movement; alert links and adjustment popup | Pending | |
| Purchasing | Current open orders/suppliers; honest missing delivery dates; create popup | Pending | |
| Accountant | Revenue/expense/report agreement; receivables; method flows; variance access | Pending | |
| Sales associate | Own sales even with reports; named-customer count; no invented assignments | Pending | |
| Custom role / multiple roles | Union of grants; explicit branch/personal restrictions still dominate | Pending | |
| Viewer / revoked membership | No unauthorized figures or actions; no stale cached disclosure | Pending | |

For each row record tenant fixture, effective permission codes, branch/sales policy, both themes, desktop/mobile, empty/error/slow scenarios, source-total comparison and the related UAT IDs. Use disposable UAT records. An accepted result needs the reviewer's name/date and evidence; failures need a defect reference and re-test.

Release gates remain pending: staging/HTTPS, representative MySQL performance, physical scanner/touch/browser testing, human screen-reader pass, cashier and manager feedback, and release-owner approval. Keep pilot flags disabled or narrowly allowlisted until those gates are accepted. Record operational feedback in [DASHBOARD_PILOT_FEEDBACK.csv](DASHBOARD_PILOT_FEEDBACK.csv).
