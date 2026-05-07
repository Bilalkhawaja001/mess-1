<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (! Schema::hasColumn('complaints', 'member_id')) {
                $table->foreignId('member_id')->nullable()->after('user_id')->constrained('members')->nullOnDelete();
            }

            if (! Schema::hasColumn('complaints', 'message')) {
                $table->text('message')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            if (Schema::hasColumn('complaints', 'member_id')) {
                $table->dropConstrainedForeignId('member_id');
            }

            if (Schema::hasColumn('complaints', 'message')) {
                $table->dropColumn('message');
            }
        });
    }
};
