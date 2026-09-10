<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_staff', function (Blueprint $t) {
            $t->id();
            $t->string('staff_code', 50)->unique();
            $t->string('name', 120);
            $t->string('username', 60)->unique();
            $t->string('password');
            $t->string('mobile_number', 20)->nullable();
            $t->boolean('is_active')->default(true);
            $t->boolean('can_view_outstanding')->default(false);
            $t->string('api_token_hash', 64)->nullable()->index();
            $t->timestamp('last_login_at')->nullable();
            $t->unsignedBigInteger('created_by_user_id')->nullable();
            $t->timestamps();
        });

        Schema::create('kitchen_pos', function (Blueprint $t) {
            $t->id();
            $t->string('temp_number', 30)->unique();
            $t->unsignedBigInteger('kitchen_staff_id')->index();
            $t->unsignedBigInteger('vendor_id')->index();
            $t->date('po_date');
            $t->text('remarks')->nullable();
            $t->string('status', 20)->default('PENDING')->index();
            $t->text('reject_reason')->nullable();
            $t->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->unsignedBigInteger('purchase_order_id')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('kitchen_po_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('kitchen_po_id')->index();
            $t->unsignedBigInteger('item_id')->index();
            $t->decimal('qty_ordered', 12, 3);
            $t->decimal('unit_price', 12, 2)->nullable();
            $t->timestamps();
        });

        Schema::create('kitchen_grns', function (Blueprint $t) {
            $t->id();
            $t->string('temp_number', 30)->unique();
            $t->unsignedBigInteger('kitchen_staff_id')->index();
            $t->unsignedBigInteger('purchase_order_id')->index();
            $t->date('received_date');
            $t->text('remarks')->nullable();
            $t->string('status', 20)->default('PENDING')->index();
            $t->text('reject_reason')->nullable();
            $t->unsignedBigInteger('reviewed_by_user_id')->nullable();
            $t->timestamp('reviewed_at')->nullable();
            $t->unsignedBigInteger('goods_receipt_id')->nullable()->index();
            $t->timestamps();
        });

        Schema::create('kitchen_grn_lines', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('kitchen_grn_id')->index();
            $t->unsignedBigInteger('purchase_order_line_id')->nullable()->index();
            $t->unsignedBigInteger('item_id')->index();
            $t->decimal('qty_received', 12, 3);
            $t->decimal('unit_cost', 12, 2)->nullable();
            $t->timestamps();
        });

        Schema::table('users', function (Blueprint $t) {
            $t->string('app_token_hash', 64)->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn('app_token_hash');
        });
        Schema::dropIfExists('kitchen_grn_lines');
        Schema::dropIfExists('kitchen_grns');
        Schema::dropIfExists('kitchen_po_lines');
        Schema::dropIfExists('kitchen_pos');
        Schema::dropIfExists('kitchen_staff');
    }
};
