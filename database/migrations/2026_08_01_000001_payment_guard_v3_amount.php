<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public $withinTransaction = false;

    private string $table = 'payments';
    private string $v1Index = 'uq_payments_active_month_guard_key';
    private string $v2Index = 'uq_payments_active_month_guard_key_v2';
    private string $v3Column = 'active_month_guard_key_v3';
    private string $v3Index  = 'uq_payments_active_month_guard_key_v3';

    private array $activeStatuses = [
        'PENDING','INITIATED','SUCCESS',
        'RECONCILIATION_PENDING','APPROVED','RECONCILED',
    ];

    public function up(): void
    {
        // Safety: koi same member+month+amount active duplicate to nahi (warna v3 unique fail)
        $in = implode(',', array_fill(0, count($this->activeStatuses), '?'));
        $dups = DB::select("
            SELECT member_id, month_cycle, amount, COUNT(*) c
            FROM `{$this->table}`
            WHERE status IN ({$in}) AND month_cycle IS NOT NULL
            GROUP BY member_id, month_cycle, amount
            HAVING COUNT(*) > 1 LIMIT 1
        ", $this->activeStatuses);
        if (! empty($dups)) {
            $d = $dups[0];
            throw new RuntimeException("v3 duplicate exists: member={$d->member_id} month={$d->month_cycle} amount={$d->amount}");
        }

        // v3 generated column (member:month:amount)
        if (! Schema::hasColumn($this->table, $this->v3Column)) {
            $quoted = implode(',', array_map(
                fn ($s) => "'".str_replace("'", "''", $s)."'",
                $this->activeStatuses
            ));
            DB::statement("
                ALTER TABLE `{$this->table}`
                ADD COLUMN `{$this->v3Column}` VARCHAR(120)
                GENERATED ALWAYS AS (
                    CASE
                        WHEN `status` IN ({$quoted})
                         AND `duplicate_guard_version` IS NOT NULL
                         AND `month_cycle` IS NOT NULL
                        THEN CONCAT(`member_id`, ':', `month_cycle`, ':', CAST(`amount` AS CHAR))
                        ELSE NULL
                    END
                ) VIRTUAL
            ");
        }

        // Naya v3 unique index
        if (! $this->indexExists($this->v3Index)) {
            DB::statement("ALTER TABLE `{$this->table}` ADD UNIQUE KEY `{$this->v3Index}` (`{$this->v3Column}`)");
        }

        // Purane 2 taale hatao (columns rakho, sirf unique index drop)
        if ($this->indexExists($this->v2Index)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->v2Index}`");
        }
        if ($this->indexExists($this->v1Index)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->v1Index}`");
        }
    }

    public function down(): void
    {
        // Rollback: v3 hatao, purane 2 wapas lagao
        if ($this->indexExists($this->v3Index)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP INDEX `{$this->v3Index}`");
        }
        if (Schema::hasColumn($this->table, $this->v3Column)) {
            DB::statement("ALTER TABLE `{$this->table}` DROP COLUMN `{$this->v3Column}`");
        }
        if (! $this->indexExists($this->v2Index) && Schema::hasColumn($this->table, 'active_month_guard_key_v2')) {
            DB::statement("ALTER TABLE `{$this->table}` ADD UNIQUE KEY `{$this->v2Index}` (`active_month_guard_key_v2`)");
        }
        if (! $this->indexExists($this->v1Index) && Schema::hasColumn($this->table, 'active_month_guard_key')) {
            DB::statement("ALTER TABLE `{$this->table}` ADD UNIQUE KEY `{$this->v1Index}` (`active_month_guard_key`)");
        }
    }

    private function indexExists(string $index): bool
    {
        $row = DB::selectOne("
            SELECT COUNT(*) c FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?
        ", [$this->table, $index]);
        return ((int)($row->c ?? 0)) > 0;
    }
};
