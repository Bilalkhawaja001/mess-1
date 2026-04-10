<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('item_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('item_id');
            $table->string('unit_code', 20);
            $table->decimal('factor_to_base', 12, 4);
            $table->boolean('is_default_for_grn')->default(false);
            $table->boolean('is_default_for_kitchen')->default(false);
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('items')->onDelete('cascade');
            $table->unique(['item_id', 'unit_code']);
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->string('trans_unit_code', 20)->nullable()->after('unit_cost');
            $table->decimal('trans_quantity', 12, 3)->nullable()->after('trans_unit_code');
        });

        Schema::table('kitchen_issues', function (Blueprint $table) {
            $table->unsignedBigInteger('mess_id')->nullable()->after('item_id');
            $table->string('issue_type', 20)->default('CONSUMPTION')->after('mess_id');

            $table->foreign('mess_id')->references('id')->on('messes')->nullOnDelete();
        });

        // Seed base unit rows for existing items to satisfy TARGET_DESIGN_FREEZE invariants.
        if (Schema::hasTable('items')) {
            DB::table('items')->orderBy('id')->chunk(100, function ($items) {
                foreach ($items as $item) {
                    if (! $item->uom) {
                        continue;
                    }

                    DB::table('item_units')->updateOrInsert(
                        ['item_id' => $item->id, 'unit_code' => $item->uom],
                        [
                            'factor_to_base' => 1.0,
                            'is_default_for_grn' => true,
                            'is_default_for_kitchen' => true,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('kitchen_issues', function (Blueprint $table) {
            $table->dropForeign(['mess_id']);
            $table->dropColumn(['mess_id', 'issue_type']);
        });

        Schema::table('stock_transactions', function (Blueprint $table) {
            $table->dropColumn(['trans_unit_code', 'trans_quantity']);
        });

        Schema::dropIfExists('item_units');
    }
};
