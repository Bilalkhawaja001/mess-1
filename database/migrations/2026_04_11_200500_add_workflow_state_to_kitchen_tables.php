<?php

use App\Models\KitchenIssue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('meal_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('meal_plans', 'status')) {
                $table->string('status', 20)->default('draft')->after('planned_servings');
            }

            if (! Schema::hasColumn('meal_plans', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }
        });

        DB::table('meal_plans')->whereNull('status')->update([
            'status' => 'draft',
            'approved_at' => null,
        ]);

        Schema::table('kitchen_issues', function (Blueprint $table) {
            if (! Schema::hasColumn('kitchen_issues', 'status')) {
                $table->string('status', 20)->default('draft')->after('issue_type');
            }

            if (! Schema::hasColumn('kitchen_issues', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('status');
            }

            if (! Schema::hasColumn('kitchen_issues', 'approved_stock_txn_id')) {
                $table->unsignedBigInteger('approved_stock_txn_id')->nullable()->after('approved_at');
            }
        });

        $issueIds = DB::table('kitchen_issues')->pluck('id');

        foreach ($issueIds as $issueId) {
            $txn = DB::table('stock_transactions')
                ->where('reference_id', $issueId)
                ->whereIn('reference_type', [KitchenIssue::class, 'App\\Models\\KitchenIssue'])
                ->orderBy('id')
                ->first();

            DB::table('kitchen_issues')->where('id', $issueId)->update($txn
                ? [
                    'status' => 'approved',
                    'approved_at' => $txn->txn_at ?? null,
                    'approved_stock_txn_id' => $txn->id,
                ]
                : [
                    'status' => 'draft',
                    'approved_at' => null,
                    'approved_stock_txn_id' => null,
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('kitchen_issues', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_issues', 'approved_stock_txn_id')) {
                $table->dropColumn('approved_stock_txn_id');
            }
            if (Schema::hasColumn('kitchen_issues', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('kitchen_issues', 'status')) {
                $table->dropColumn('status');
            }
        });

        Schema::table('meal_plans', function (Blueprint $table) {
            if (Schema::hasColumn('meal_plans', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('meal_plans', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};
