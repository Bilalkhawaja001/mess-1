# PRIORITY_MATRIX.md

## P0 — Launch Blockers (Must complete before production launch)

| Item | Why P0 | Risk if ignored |
|---|---|---|
| Password reset + change-password parity (`/change-password`, `/password-reset/*`) | Account recovery and credential hygiene are mandatory | lockouts, insecure manual resets, operational downtime |
| Month lifecycle controls (`close/reopen/hard reset`) | Billing-period governance is core financial control | accidental post-close mutations, audit failure |
| Billing correction endpoint with reversal trace | Real-world billing errors are unavoidable | unreconciled accounts, manual off-system fixes |
| Payment edit + strict state transitions | Correcting payment entry mistakes is operational necessity | ledger mismatch, double posting, inconsistent approval history |
| Ledger import/opening-balance/recompute toolchain | Migration/cutover and correction workflows depend on this | impossible reconciliation at go-live |
| Audit log module + route | Every financial/admin mutation needs traceability | no forensic visibility, compliance risk |
| Permission model (`permissions`, `role_permissions`) | Route-level role only is insufficient for sensitive actions | privilege creep, unauthorized critical operations |
| Password reset token table + lifecycle | required by auth recovery flow | insecure workaround practices |
| Month closure persistence table | close/reopen states need durable record | inconsistent month state across requests |
| Bills download/export endpoints | operational reporting and verification needed at launch | inability to validate and share billing outputs |

---

## P1 — Required Soon After Launch (Stabilization)

| Item | Why P1 | Risk if delayed too long |
|---|---|---|
| Member bulk upload + sample + remove-selected | onboarding efficiency and cleanup | manual workload spikes, data entry errors |
| Monthly attendance approve/unlock controls | improves governance and correction management | accidental edits after review cycle |
| Rate lock/update/delete parity | prevents policy drift after approval | billing inconsistency over time |
| Member portal completion (`bill/attendance/payments/profile`) | user transparency/self-service | support burden on admin team |
| Rich admin settings parity (`/admin/settings/app`) | runtime operational control completeness | manual DB toggles, fragile operations |
| Stronger lifecycle columns for members/rates/payments | better data quality and auditability | reduced analytical reliability |

---

## P2 — Deferred / Non-Critical for Initial Launch (Full parity backlog)

| Item | Why P2 | Risk profile |
|---|---|---|
| Inventory management module | outside core launch billing backbone | medium operational gap for stores teams |
| Procurement (vendor/PO/GRN) | not required for immediate billing go-live | medium process fragmentation |
| Meal planning + recipe + kitchen issue | can run in existing Flask during transition | medium if full consolidation required early |
| Guest management + guest meals | not essential for base member billing launch | medium-low |
| Department/mess accounting suite | advanced finance layer | medium-long-term reporting gap |
| Ops/report/inventory/meals hubs | UX consolidation feature | low immediate risk |
| Debug/utility parity endpoints | support convenience only | low |

---

## Priority Rationale Framework
- **P0:** Financial integrity, access control, auditability, and launch-day operability.
- **P1:** High-value operational efficiency + governance hardening post-launch.
- **P2:** Full-parity breadth modules not required to run core billing safely.
