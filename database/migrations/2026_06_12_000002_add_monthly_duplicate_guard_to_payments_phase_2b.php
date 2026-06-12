<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public $withinTransaction = false;

    private array $activeStatuses = [
        'PENDING',
        'RECONCILIATION_PENDING',
        'APPROVED',
        'RECONCILED',
    ];

    private array $allowedStatuses = [
        'PENDING',
        'RECONCILIATION_PENDING',
        'APPROVED',
        'RECONCILED',
        'FAILED',
        'CANCELLED',
        'REVERSED',
    ];

    public function up(): void
    {
        $this->setFastFailTimeouts();
        $this->assertPhase2AStillValid();
        $this->assertStatusesAreKnown();

        $this->addNormalGuardColumnsIfMissing();
        $this->addGeneratedGuardColumnIfMissing();

        $this->assertNoGeneratedGuardDuplicatesBeforeIndex();

        $this->addUniqueGuardIndexIfMissing();
        $this->createOrReplaceNullSafetyTriggers();

        $this->postVerifyUp();
    }

    public function down(): void
    {
        $this->setFastFailTimeouts();

        DB::unprepared("DROP TRIGGER IF EXISTS `trg_payments_guard_month_cycle_bi`");
        DB::unprepared("DROP TRIGGER IF EXISTS `trg_payments_guard_month_cycle_bu`");

        if ($this->indexExists('payments', 'uq_payments_active_month_guard_key')) {
            DB::unprepared("
                ALTER TABLE `payments`
                DROP INDEX `uq_payments_active_month_guard_key`,
                ALGORITHM=INPLACE,
                LOCK=NONE
            ");
        }

        if ($this->columnExists('payments', 'active_month_guard_key')) {
            DB::unprepared("
                ALTER TABLE `payments`
                DROP COLUMN `active_month_guard_key`,
                ALGORITHM=INPLACE,
                LOCK=NONE
            ");
        }

        if ($this->columnExists('payments', 'duplicate_guard_version')) {
            DB::unprepared("
                ALTER TABLE `payments`
                DROP COLUMN `duplicate_guard_version`,
                ALGORITHM=INPLACE,
                LOCK=NONE
            ");
        }

        if ($this->columnExists('payments', 'month_cycle')) {
            DB::unprepared("
                ALTER TABLE `payments`
                DROP COLUMN `month_cycle`,
                ALGORITHM=INPLACE,
                LOCK=NONE
            ");
        }

        $this->postVerifyDown();
    }

    private function setFastFailTimeouts(): void
    {
        DB::statement("SET SESSION lock_wait_timeout = 5");
        DB::statement("SET SESSION innodb_lock_wait_timeout = 5");
    }

    private function assertPhase2AStillValid(): void
    {
        $fk = DB::selectOne("
            SELECT
                kcu.CONSTRAINT_NAME,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            LEFT JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
              ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA
             AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
            WHERE kcu.TABLE_SCHEMA = DATABASE()
              AND kcu.TABLE_NAME = 'payments'
              AND kcu.COLUMN_NAME = 'bill_id'
              AND kcu.REFERENCED_TABLE_NAME = 'billings'
              AND kcu.REFERENCED_COLUMN_NAME = 'id'
            LIMIT 1
        ");

        $deleteRule = strtoupper((string) ($fk->DELETE_RULE ?? ''));

        if (
            !$fk
            || ($fk->CONSTRAINT_NAME ?? '') !== 'payments_bill_id_restrict_foreign'
            || !in_array($deleteRule, ['RESTRICT', 'NO ACTION'], true)
        ) {
            throw new RuntimeException('Phase 2A FK verification failed. Refusing Phase 2B migration.');
        }

        $trigger = DB::selectOne("
            SELECT TRIGGER_NAME
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME = 'trg_billings_lock_month_cycle_when_payments_exist_bu'
            LIMIT 1
        ");

        if (!$trigger) {
            throw new RuntimeException('Phase 2A billings month_cycle lock trigger missing. Refusing Phase 2B migration.');
        }

        $orphans = (int) DB::table('payments as p')
            ->leftJoin('billings as b', 'b.id', '=', 'p.bill_id')
            ->whereNotNull('p.bill_id')
            ->whereNull('b.id')
            ->count();

        if ($orphans !== 0) {
            throw new RuntimeException("Orphan payments found: {$orphans}. Refusing Phase 2B migration.");
        }
    }

    private function assertStatusesAreKnown(): void
    {
        $statuses = DB::table('payments')
            ->select('status')
            ->distinct()
            ->orderBy('status')
            ->pluck('status')
            ->map(fn ($status) => (string) $status)
            ->values()
            ->all();

        $unexpected = array_values(array_diff($statuses, $this->allowedStatuses));

        if (count($unexpected) > 0) {
            throw new RuntimeException('Unexpected payment statuses found: ' . implode(', ', $unexpected));
        }
    }

    private function addNormalGuardColumnsIfMissing(): void
    {
        $adds = [];

        if (!$this->columnExists('payments', 'month_cycle')) {
            $adds[] = "ADD COLUMN `month_cycle` VARCHAR(20) NULL AFTER `bill_id`";
        }

        if (!$this->columnExists('payments', 'duplicate_guard_version')) {
            $adds[] = "ADD COLUMN `duplicate_guard_version` VARCHAR(20) NULL AFTER `status`";
        }

        if (count($adds) === 0) {
            return;
        }

        DB::unprepared("
            ALTER TABLE `payments`
            " . implode(",\n            ", $adds) . ",
            ALGORITHM=INPLACE,
            LOCK=NONE
        ");
    }

    private function addGeneratedGuardColumnIfMissing(): void
    {
        if ($this->columnExists('payments', 'active_month_guard_key')) {
            return;
        }

        DB::unprepared("
            ALTER TABLE `payments`
            ADD COLUMN `active_month_guard_key` VARCHAR(80)
            GENERATED ALWAYS AS (
                CASE
                    WHEN `status` IN ('PENDING','RECONCILIATION_PENDING','APPROVED','RECONCILED')
                         AND `duplicate_guard_version` IS NOT NULL
                         AND `month_cycle` IS NOT NULL
                    THEN CONCAT(`member_id`, ':', `month_cycle`)
                    ELSE NULL
                END
            ) VIRTUAL,
            ALGORITHM=INPLACE,
            LOCK=NONE
        ");
    }

    private function assertNoGeneratedGuardDuplicatesBeforeIndex(): void
    {
        if (
            !$this->columnExists('payments', 'month_cycle')
            || !$this->columnExists('payments', 'duplicate_guard_version')
            || !$this->columnExists('payments', 'active_month_guard_key')
        ) {
            throw new RuntimeException('Guard columns not fully present before duplicate audit.');
        }

        $duplicates = DB::select("
            SELECT
                `member_id`,
                `month_cycle`,
                COUNT(*) AS total
            FROM `payments`
            WHERE `status` IN ('PENDING','RECONCILIATION_PENDING','APPROVED','RECONCILED')
              AND `duplicate_guard_version` IS NOT NULL
              AND `month_cycle` IS NOT NULL
            GROUP BY `member_id`, `month_cycle`
            HAVING COUNT(*) > 1
            LIMIT 10
        ");

        if (count($duplicates) > 0) {
            throw new RuntimeException('Non-NULL duplicate guard keys exist before index creation. Refusing migration.');
        }
    }

    private function addUniqueGuardIndexIfMissing(): void
    {
        if ($this->indexExists('payments', 'uq_payments_active_month_guard_key')) {
            return;
        }

        DB::unprepared("
            ALTER TABLE `payments`
            ADD UNIQUE KEY `uq_payments_active_month_guard_key` (`active_month_guard_key`),
            ALGORITHM=INPLACE,
            LOCK=NONE
        ");
    }

    private function createOrReplaceNullSafetyTriggers(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS `trg_payments_guard_month_cycle_bi`");
        DB::unprepared("DROP TRIGGER IF EXISTS `trg_payments_guard_month_cycle_bu`");

        DB::unprepared("
            CREATE TRIGGER `trg_payments_guard_month_cycle_bi`
            BEFORE INSERT ON `payments`
            FOR EACH ROW
            BEGIN
                IF NEW.`duplicate_guard_version` IS NOT NULL
                   AND NEW.`month_cycle` IS NULL
                THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'month_cycle is required when duplicate_guard_version is set';
                END IF;
            END
        ");

        DB::unprepared("
            CREATE TRIGGER `trg_payments_guard_month_cycle_bu`
            BEFORE UPDATE ON `payments`
            FOR EACH ROW
            BEGIN
                IF NEW.`duplicate_guard_version` IS NOT NULL
                   AND NEW.`month_cycle` IS NULL
                THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'month_cycle is required when duplicate_guard_version is set';
                END IF;
            END
        ");
    }

    private function postVerifyUp(): void
    {
        foreach (['month_cycle', 'duplicate_guard_version', 'active_month_guard_key'] as $column) {
            if (!$this->columnExists('payments', $column)) {
                throw new RuntimeException("Post-verify failed: missing column {$column}");
            }
        }

        if (!$this->indexExists('payments', 'uq_payments_active_month_guard_key')) {
            throw new RuntimeException('Post-verify failed: unique guard index missing');
        }

        foreach (['trg_payments_guard_month_cycle_bi', 'trg_payments_guard_month_cycle_bu'] as $trigger) {
            if (!$this->triggerExists($trigger)) {
                throw new RuntimeException("Post-verify failed: missing trigger {$trigger}");
            }
        }
    }

    private function postVerifyDown(): void
    {
        foreach (['trg_payments_guard_month_cycle_bi', 'trg_payments_guard_month_cycle_bu'] as $trigger) {
            if ($this->triggerExists($trigger)) {
                throw new RuntimeException("Post-down verify failed: trigger still exists {$trigger}");
            }
        }

        if ($this->indexExists('payments', 'uq_payments_active_month_guard_key')) {
            throw new RuntimeException('Post-down verify failed: unique guard index still exists');
        }

        foreach (['active_month_guard_key', 'duplicate_guard_version', 'month_cycle'] as $column) {
            if ($this->columnExists('payments', $column)) {
                throw new RuntimeException("Post-down verify failed: column still exists {$column}");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ", [$table, $column]);

        return ((int) ($row->total ?? 0)) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ", [$table, $index]);

        return ((int) ($row->total ?? 0)) > 0;
    }

    private function triggerExists(string $trigger): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS total
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME = ?
        ", [$trigger]);

        return ((int) ($row->total ?? 0)) > 0;
    }
};
