<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('sku')->unique(); $table->string('uom', 20)->default('kg');
            $table->decimal('reorder_level', 12, 3)->default(0); $table->boolean('is_active')->default(true); $table->timestamps();
        });
        Schema::create('stock_transactions', function (Blueprint $table) {
            $table->id(); $table->foreignId('item_id')->constrained('items')->cascadeOnDelete(); $table->string('txn_type', 40);
            $table->decimal('quantity', 12, 3); $table->decimal('unit_cost', 12, 2)->default(0); $table->string('reference_type', 80)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable(); $table->text('remarks')->nullable(); $table->timestamp('txn_at'); $table->timestamps();
        });
        Schema::create('vendors', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('contact_person')->nullable(); $table->string('phone', 40)->nullable(); $table->string('email')->nullable(); $table->text('address')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('purchase_orders', function (Blueprint $table) { $table->id(); $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete(); $table->string('po_number')->unique(); $table->date('po_date'); $table->string('status', 30)->default('DRAFT'); $table->text('remarks')->nullable(); $table->timestamps(); });
        Schema::create('purchase_order_lines', function (Blueprint $table) { $table->id(); $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete(); $table->foreignId('item_id')->constrained('items')->restrictOnDelete(); $table->decimal('qty_ordered', 12, 3); $table->decimal('unit_price', 12, 2)->default(0); $table->timestamps(); });
        Schema::create('goods_receipts', function (Blueprint $table) { $table->id(); $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete(); $table->string('grn_number')->unique(); $table->date('received_date'); $table->text('remarks')->nullable(); $table->timestamps(); });
        Schema::create('goods_receipt_lines', function (Blueprint $table) { $table->id(); $table->foreignId('goods_receipt_id')->constrained('goods_receipts')->cascadeOnDelete(); $table->foreignId('item_id')->constrained('items')->restrictOnDelete(); $table->decimal('qty_received', 12, 3); $table->decimal('unit_cost', 12, 2)->default(0); $table->timestamps(); });
        Schema::create('menus', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('meal_type', 30); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('recipes', function (Blueprint $table) { $table->id(); $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete(); $table->foreignId('item_id')->constrained('items')->restrictOnDelete(); $table->decimal('qty_per_serving', 12, 4); $table->timestamps(); });
        Schema::create('meal_plans', function (Blueprint $table) { $table->id(); $table->date('plan_date'); $table->foreignId('menu_id')->constrained('menus')->restrictOnDelete(); $table->unsignedInteger('planned_servings'); $table->timestamps(); });
        Schema::create('kitchen_issues', function (Blueprint $table) { $table->id(); $table->date('issue_date'); $table->foreignId('item_id')->constrained('items')->restrictOnDelete(); $table->decimal('quantity', 12, 3); $table->text('remarks')->nullable(); $table->timestamps(); });
        Schema::create('guests', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('contact', 80)->nullable(); $table->string('department')->nullable(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('guest_meals', function (Blueprint $table) { $table->id(); $table->foreignId('guest_id')->constrained('guests')->cascadeOnDelete(); $table->date('meal_date'); $table->string('meal_type', 30); $table->unsignedInteger('quantity')->default(1); $table->decimal('rate', 12, 2); $table->decimal('amount', 12, 2); $table->timestamps(); });
        Schema::create('departments', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('code', 30)->unique(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('messes', function (Blueprint $table) { $table->id(); $table->string('name'); $table->string('code', 30)->unique(); $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete(); $table->boolean('is_active')->default(true); $table->timestamps(); });
        Schema::create('department_ledgers', function (Blueprint $table) { $table->id(); $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete(); $table->foreignId('mess_id')->nullable()->constrained('messes')->nullOnDelete(); $table->date('entry_date'); $table->string('entry_type', 20); $table->decimal('amount', 12, 2); $table->string('reference_type', 80)->nullable(); $table->unsignedBigInteger('reference_id')->nullable(); $table->text('remarks')->nullable(); $table->timestamps(); });
    }
    public function down(): void { foreach (['department_ledgers','messes','departments','guest_meals','guests','kitchen_issues','meal_plans','recipes','menus','goods_receipt_lines','goods_receipts','purchase_order_lines','purchase_orders','vendors','stock_transactions','items'] as $t) { Schema::dropIfExists($t); } }
};
