<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            if (! Schema::hasColumn('members', 'mobile_number')) {
                $table->string('mobile_number', 20)->nullable()->after('department_name');
            }
            if (! Schema::hasColumn('members', 'portal_enabled')) {
                $table->boolean('portal_enabled')->default(true)->after('is_active');
            }
            if (! Schema::hasColumn('members', 'mobile_verified_at')) {
                $table->timestamp('mobile_verified_at')->nullable()->after('portal_enabled');
            }
            if (! Schema::hasColumn('members', 'registered_at')) {
                $table->timestamp('registered_at')->nullable()->after('mobile_verified_at');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'member_id')) {
                $table->foreignId('member_id')->nullable()->after('role_id')->constrained('members')->nullOnDelete();
                $table->unique('member_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'member_id')) {
                $table->dropUnique(['member_id']);
                $table->dropConstrainedForeignId('member_id');
            }
        });

        Schema::table('members', function (Blueprint $table) {
            foreach (['registered_at', 'mobile_verified_at', 'portal_enabled', 'mobile_number'] as $column) {
                if (Schema::hasColumn('members', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
