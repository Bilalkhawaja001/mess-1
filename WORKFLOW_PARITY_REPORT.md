# WORKFLOW_PARITY_REPORT.md

## Scope
End-to-end parity mapping for requested workflows:
- auth
- member lifecycle
- billing run
- payments
- reports
- month close/reopen
- admin settings

Legend:
- **Covered** = implemented in Laravel route/controller surface
- **Partial** = base exists, key control points missing
- **Missing** = no parity surface

---

## 1) Authentication Workflow
**Flask canonical:** login/logout + change-password + password reset request/consume + token lifecycle.

**Laravel current:** login/logout only.

**Coverage:** **Partial (P0 gap)**

**Missing for parity:**
- change-password flow
- password reset token generation/expiry/consume
- forced-password-change handling (`must_change_password` parity)

**Acceptance criteria:**
1. User can reset password via token with expiry enforcement.
2. Logged-in user can change password with old-password verification.
3. All auth-sensitive actions logged to audit.

---

## 2) Member Lifecycle Workflow
**Flask canonical:** create/edit/deactivate/reactivate/remove + bulk upload/sample + guarded deletion.

**Laravel current:** create/update/toggle-active.

**Coverage:** **Partial (P0/P1 mixed)**

**Missing for parity:**
- bulk upload/sample endpoints
- explicit remove-selected semantics
- stronger lifecycle state transitions with audit

**Acceptance criteria:**
1. Member activation state transition is reversible and auditable.
2. Bulk onboarding handles validation + duplicate member ID conflicts.
3. Historical billing/ledger rows remain consistent after member state change.

---

## 3) Billing Run Workflow
**Flask canonical:** attendance+extras+approved rates => generate bills; lock state; correction/reversal handling; run hash/idempotency.

**Laravel current:** billing list + generate.

**Coverage:** **Partial (P0 gap)**

**Missing for parity:**
- bill correction endpoint and reversal trail fields
- explicit lock/unlock governance
- complete idempotency and audit parity on reruns

**Acceptance criteria:**
1. Generate billing for month creates one consistent run signature.
2. Correction produces auditable reversal/adjustment entries.
3. Unauthorized users cannot mutate locked billed periods.

---

## 4) Payments Workflow
**Flask canonical:** payment create/edit/approve with status transitions and ledger reflection.

**Laravel current:** create + approve.

**Coverage:** **Partial (P0 gap)**

**Missing for parity:**
- payment edit endpoint
- richer approval fields and reason/origin metadata
- full guardrails for approved payment mutation

**Acceptance criteria:**
1. Payment edit allowed only in permitted state/role.
2. Approval writes immutable audit + ledger impact.
3. Duplicate posting prevented by validation and transactional constraints.

---

## 5) Reports Workflow
**Flask canonical:** summary/reports/statement + bills-download/export CSV/XLSX + recovery/finance report family.

**Laravel current:** summary/reports/statement pages.

**Coverage:** **Partial (P0 for bill export, P1/P2 for extended report family)**

**Missing for parity:**
- bills download/export endpoints
- broader recovery/finance report parity

**Acceptance criteria:**
1. Bills export matches canonical report totals/filter behavior.
2. Statement and summary numbers reconcile against ledger and billings.
3. Export actions are access-controlled and auditable.

---

## 6) Month Close / Reopen Workflow
**Flask canonical:** close, reopen, hard reset with role restrictions and state gates.

**Laravel current:** no equivalent endpoints.

**Coverage:** **Missing (P0)**

**Missing for parity:**
- month closure table and actions
- reopen gate checks
- hard reset safeguard workflow

**Acceptance criteria:**
1. Month close blocks mutable operations for closed cycle.
2. Reopen requires privileged role + reason capture.
3. Hard reset is super-admin only with strong audit trail.

---

## 7) Admin Settings Workflow
**Flask canonical:** settings page + app settings endpoint (`/admin/settings/app`) with operational flags.

**Laravel current:** basic settings list/store/toggle.

**Coverage:** **Partial (P0/P1 depending on missing flags)**

**Missing for parity:**
- richer tabbed setting groups
- all operational controls present in Flask admin settings

**Acceptance criteria:**
1. Required runtime config keys exist and are validated by type.
2. Changes are role-gated and logged.
3. Setting changes take effect predictably without breaking billing runs.

---

## Consolidated Workflow Status

| Workflow | Current parity | Launch impact |
|---|---|---|
| Auth | Partial | P0 |
| Member lifecycle | Partial | P0/P1 |
| Billing run | Partial | P0 |
| Payments | Partial | P0 |
| Reports | Partial | P0/P1 |
| Month close/reopen/reset | Missing | P0 |
| Admin settings | Partial | P0/P1 |

---

## Non-launch Full-Parity Workflows (tracked, deferred)
- Inventory procurement cycle (item -> PO -> GRN -> stock approvals)
- Meal planning to kitchen issue to consumption analysis
- Guest meal billing/approval/export
- Department/mess costing and accounting workflows

These are **P2** for launch but required for full Flask parity.
