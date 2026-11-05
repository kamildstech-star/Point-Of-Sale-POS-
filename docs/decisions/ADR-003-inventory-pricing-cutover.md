# ADR-003: Stock posting, new pricing and branch activation

Date: 2026-10-19. Status: implemented locally; physical location review/activation and production deployment remain owner/operator actions.

The owner approved distinct school/product/size SKUs in phase 3. For phase 5 the owner also approved tax-exclusive catalog pricing, half-up rounding to currency minor units, and credit for named customers while requiring full payment for walk-ins. Branch credit can be disabled.

Use DECIMAL(19,4) stored amounts and DECIMAL(18,4) quantities, BCMath for exact intermediate arithmetic and one centralized currency-exponent definition. Price × quantity is rounded per line to currency precision. Allocate a fixed order discount proportionally across remaining line bases with a final residual, apply the effective configured tax to the discounted line, round half-up and sum the stored line amounts. Request numbers never supply final prices/tax/totals. Unsupported currency exponents block activation. PKR is two places. Current/default cost snapshots are not final COGS.

Use one tenant mutex before deterministic balance/document locks. It sacrifices parallel throughput within a tenant for a simple common order across legacy writers, cutover, stock, purchasing, invoice sequences and payments. Transactions contain only database work. Source-event uniqueness and request-key/payload hashes make retries safe, and MariaDB movement triggers prohibit in-place ledger edits. A future lock-granularity optimization requires the same concurrent-sale/receipt/return tests.

Keep legacy pricing and historical school margins/discounts in their original tables/snapshots. Do not replay historical sales into new balances. An owner approves a fresh source hash and physical-location review; the first legacy branch activation freezes every old stock-writing route and imports one movement per legacy stock row at that instant. The old data has no branch dimension, so allowing legacy writes for another old branch would be unsafe. Other new branches retain independent checkout activation flags. History remains readable, without silently routing old cancellations into the new ledger.

Separate retail customers from authenticating users. Add safe order/customer links, retain original IDs and credentials, and queue staff/missing/ambiguous identities for explicit review. Never infer historical refunds or fabricate payments. New receipts snapshot names, prices, units, tax/discount, branch and customer identity.

External terminal/bank payments are manual confirmations with references. No gateway charge/capture, refund, full accounting, cost-layer valuation, transfer engine or optional batch/serial allocation is introduced by these phases. Shift opening and method reconciliation are phase 5; close/cash movements and retail returns follow in phase 6.
