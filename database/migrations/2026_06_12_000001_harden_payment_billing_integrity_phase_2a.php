<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private string $oldFkName = 'payments_bill_id_foreign';
    private string $newFkName = 'payments_bill_id_restrict_foreign';
    private string $triggerName = 'trg_billings_lock_month_cycle_when_payments_exist_bu';

    public function up(): void
    {
        DB::unprepared("
            ALTER TABLE payments
            DROP FOREIGN KEY {$this->oldFkName},
            ADD CONSTRAINT {$this->newFkName}
                FOREIGN KEY (bill_id)
                REFERENCES billings (id)
                ON DELETE RESTRICT
        ");

        DB::unprepared("DROP TRIGGER IF EXISTS {$this->triggerName}");

        DB::unprepared("
            CREATE TRIGGER {$this->triggerName}
            BEFORE UPDATE ON billings
            FOR EACH ROW
            BEGIN
                IF NOT (OLD.month_cycle <=> NEW.month_cycle)
                   AND EXISTS (
                        SELECT 1
                        FROM payments
                        WHERE bill_id = OLD.id
                        LIMIT 1
                   )
                THEN
                    SIGNAL SQLSTATE '45000'
                        SET MESSAGE_TEXT = 'Billing month cannot be changed because payments already exist for this bill.';
                END IF;
            END
        ");
    }

    public function down(): void
    {
        DB::unprepared("DROP TRIGGER IF EXISTS {$this->triggerName}");

        DB::unprepared("
            ALTER TABLE payments
            DROP FOREIGN KEY {$this->newFkName},
            ADD CONSTRAINT {$this->oldFkName}
                FOREIGN KEY (bill_id)
                REFERENCES billings (id)
                ON DELETE SET NULL
        ");
    }
};
