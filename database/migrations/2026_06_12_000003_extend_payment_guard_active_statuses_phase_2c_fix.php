<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $table = 'payments';

    private string $v1GuardColumn = 'active_month_guard_key';
    private string $v1GuardIndex = 'uq_payments_active_month_guard_key';

    private string $v2GuardColumn = 'active_month_guard_key_v2';
    private string $v2GuardIndex = 'uq_payments_active_month_guard_key_v2';

    private array $expandedActiveStatuses = [
        'PENDING',
        'INITIATED',
        'SUCCESS',
        'RECONCILIATION_PENDING',
        'APPROVED',
        'RECONCILED',
    ];

    public function up(): void
    {
        $this->assertPhase2BReady();
        $this->assertNoDuplicatesForExpandedGuard();

        if (! $this->columnExists($this->table, $this->v2GuardColumn)) {
            DB::statement($this->addGeneratedGuardColumnSql($this->v2GuardColumn, $this->expandedActiveStatuses));
        }

        if (! $this->indexExists($this->table, $this->v2GuardIndex)) {
            DB::statement("ALTER TABLE `{$this->table}` ADD UNIQUE KEY `{$this->v2GuardIndex}` (`{$this->v2GuardColumn}`)");
        }

        $this->assertGeneratedExpressionContains($this->v2GuardColumn, $this->expandedActiveStatuses);
    }

    public function down(): void
    {
        if ($this->indexExists($this->table, $this->v2GuardIndex)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->v2GuardIndex}`");
        }

        if ($this->columnExists($this->table, $this->v2GuardColumn)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP COLUMN `{$this->v2GuardColumn}`");
        }
    }

    private function assertPhase2BReady(): void
    {
        foreach (['member_id', 'bill_id', 'status', 'month_cycle', 'duplicate_guard_version', $this->v1GuardColumn] as $column) {
            if (! $this->columnExists($this->table, $column)) {
                throw new RuntimeException("Required payments column missing: {$column}");
            }
        }

        if (! $this->indexExists($this->table, $this->v1GuardIndex)) {
            throw new RuntimeException("Phase 2B guard unique index missing: {$this->v1GuardIndex}");
        }

        foreach (['trg_payments_guard_month_cycle_bi', 'trg_payments_guard_month_cycle_bu'] as $trigger) {
            if (! $this->triggerExists($trigger)) {
                throw new RuntimeException("Guard trigger missing: {$trigger}");
            }
        }
    }

    private function assertNoDuplicatesForExpandedGuard(): void
    {
        $placeholders = implode(',', array_fill(0, count($this->expandedActiveStatuses), '?'));

        $duplicates = DB::select("
            SELECT
                member_id,
                month_cycle,
                COUNT(*) AS duplicate_count,
                GROUP_CONCAT(id ORDER BY id) AS payment_ids,
                GROUP_CONCAT(status ORDER BY id) AS statuses
            FROM `{$this->table}`
            WHERE status IN ({$placeholders})
              AND duplicate_guard_version IS NOT NULL
              AND month_cycle IS NOT NULL
            GROUP BY member_id, month_cycle
            HAVING COUNT(*) > 1
            LIMIT 10
        ", $this->expandedActiveStatuses);

        if (! empty($duplicates)) {
            $first = $duplicates[0];
            throw new RuntimeException(
                'Expanded guard duplicate conflict found before DDL. member_id=' . $first->member_id .
                ', month_cycle=' . $first->month_cycle .
                ', payment_ids=' . $first->payment_ids .
                ', statuses=' . $first->statuses
            );
        }
    }

    private function addGeneratedGuardColumnSql(string $column, array $statuses): string
    {
        $quotedStatuses = implode(',', array_map(
            fn (string $status): string => "'" . str_replace("'", "''", $status) . "'",
            $statuses
        ));

        return "
            ALTER TABLE `{$this->table}`
            ADD COLUMN `{$column}` VARCHAR(80)
            GENERATED ALWAYS AS (
                CASE
                    WHEN `status` IN ({$quotedStatuses})
                     AND `duplicate_guard_version` IS NOT NULL
                     AND `month_cycle` IS NOT NULL
                    THEN CONCAT(`member_id`, ':', `month_cycle`)
                    ELSE NULL
                END
            ) VIRTUAL
        ";
    }

    private function assertGeneratedExpressionContains(string $column, array $requiredStatuses): void
    {
        $row = DB::selectOne("
            SELECT GENERATION_EXPRESSION
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ", [$this->table, $column]);

        $expression = strtoupper((string) ($row->GENERATION_EXPRESSION ?? ''));

        foreach ($requiredStatuses as $status) {
            if (! str_contains($expression, strtoupper($status))) {
                throw new RuntimeException("Generated guard expression missing status: {$status}");
            }
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        return Schema::hasColumn($table, $column);
    }

    private function indexExists(string $table, string $index): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND INDEX_NAME = ?
        ", [$table, $index]);

        return ((int) ($row->c ?? 0)) > 0;
    }

    private function triggerExists(string $trigger): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) AS c
            FROM information_schema.TRIGGERS
            WHERE TRIGGER_SCHEMA = DATABASE()
              AND TRIGGER_NAME = ?
        ", [$trigger]);

        return ((int) ($row->c ?? 0)) > 0;
    }
};
