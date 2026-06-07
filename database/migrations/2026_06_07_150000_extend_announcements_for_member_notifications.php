<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            if (! Schema::hasColumn('announcements', 'severity')) {
                $table->string('severity', 20)->default('normal')->after('target_type');
            }

            if (! Schema::hasColumn('announcements', 'target_member_ids')) {
                $table->json('target_member_ids')->nullable()->after('severity');
            }
        });

        if (! Schema::hasTable('member_announcement_reads')) {
            Schema::create('member_announcement_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained('announcements')->cascadeOnDelete();
                $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->unique(['announcement_id', 'member_id'], 'member_announcement_reads_unique');
                $table->index(['member_id', 'read_at'], 'member_announcement_reads_member_read_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_announcement_reads');

        Schema::table('announcements', function (Blueprint $table) {
            if (Schema::hasColumn('announcements', 'target_member_ids')) {
                $table->dropColumn('target_member_ids');
            }

            if (Schema::hasColumn('announcements', 'severity')) {
                $table->dropColumn('severity');
            }
        });
    }
};
