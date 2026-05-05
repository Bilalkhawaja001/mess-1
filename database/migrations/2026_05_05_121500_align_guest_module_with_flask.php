<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('guests', function (Blueprint $table) {
            if (! Schema::hasColumn('guests', 'guest_code')) {
                $table->string('guest_code', 50)->nullable()->after('id');
            }
            if (! Schema::hasColumn('guests', 'date')) {
                $table->date('date')->nullable()->after('guest_code');
            }
            if (! Schema::hasColumn('guests', 'came_from')) {
                $table->string('came_from', 120)->nullable()->after('name');
            }
            if (! Schema::hasColumn('guests', 'remarks')) {
                $table->text('remarks')->nullable()->after('came_from');
            }
            if (! Schema::hasColumn('guests', 'department_id')) {
                $table->foreignId('department_id')->nullable()->after('remarks')->constrained('departments')->nullOnDelete();
            }
            if (! Schema::hasColumn('guests', 'host_member_id')) {
                $table->foreignId('host_member_id')->nullable()->after('department_id')->constrained('members')->nullOnDelete();
            }
            if (! Schema::hasColumn('guests', 'is_deleted')) {
                $table->boolean('is_deleted')->default(false)->after('is_active');
            }
        });

        Schema::table('guest_meals', function (Blueprint $table) {
            if (! Schema::hasColumn('guest_meals', 'posted_by')) {
                $table->foreignId('posted_by')->nullable()->after('amount')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('guest_meals', 'approved_by')) {
                $table->foreignId('approved_by')->nullable()->after('posted_by')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('guest_meals', 'posted_at')) {
                $table->timestamp('posted_at')->nullable()->after('approved_by');
            }
            if (! Schema::hasColumn('guest_meals', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('posted_at');
            }
            if (! Schema::hasColumn('guest_meals', 'rate_applied')) {
                $table->decimal('rate_applied', 12, 2)->nullable()->after('quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('guest_meals', function (Blueprint $table) {
            foreach (['approved_at', 'posted_at', 'approved_by', 'posted_by', 'rate_applied'] as $column) {
                if (Schema::hasColumn('guest_meals', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('guests', function (Blueprint $table) {
            foreach (['guest_code', 'date', 'came_from', 'remarks', 'department_id', 'host_member_id', 'is_deleted'] as $column) {
                if (Schema::hasColumn('guests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
