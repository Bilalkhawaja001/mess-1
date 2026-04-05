# 01 REPAIR PLAN

## P0 financial truth blockers
1. Repair billing generation so month closure is enforced, approved monthly attendance is primary, daily attendance is fallback only, employment window is respected at charged-day level, and rerun hash includes monthly attendance truth.
2. Repair billing correction so ledger truth is updated through explicit correction delta posting instead of simple billing row overwrite.
3. Repair month hard reset so month-scoped billing ledger effects and billing run metadata are removed together with billing rows.
4. Repair payment approval semantics so ledger posting is exact-once and coherent with payment/reconciliation lifecycle.
5. Repair guest approval so a real department chargeback ledger entry is created instead of `touch()` stub behavior.

## P1 operational blockers
1. Verify monthly attendance save/approve/unlock/export behavior and ensure generation consumes approved locked snapshots.
2. Verify reporting/statement/summary/export surfaces use truthful financial sources after P0 repairs.
3. Repair dashboard metrics so placeholder minimal counts are replaced with query-backed operational metrics.
4. Review kitchen/procurement approval semantics; document remaining parity gaps where route exists but durable business effect is still not proven.

## P2 navigation/workspace parity items
1. Preserve current route surface but document where navigation still represents workflow shells rather than proven Flask parity.
2. Keep member portal limited to proven scope; do not inflate parity claims.

## P3 polish/secondary parity items
1. Inspect special costing/accounting subsystem evidence and record what remains unproven.
2. Record settings-driven parity items left outside this repair set if no direct financial proof path exists in current truth pack.

## Execution order actually followed
1. Environment bootstrap (`composer install`, `.env`, migrate/seed)
2. Baseline audit docs
3. Truth-pack vs repo code audit
4. P0 implementation edits
5. P1 dashboard/test/report verification edits
6. Regression tests + route proof
7. Final proof docs + git push
