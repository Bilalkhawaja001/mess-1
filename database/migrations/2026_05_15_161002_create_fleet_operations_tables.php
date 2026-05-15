<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_vehicle_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('fleet_drivers')->cascadeOnDelete();
            $table->date('assigned_from')->index();
            $table->date('assigned_to')->nullable()->index();
            $table->string('status')->default('Active')->index();
            $table->timestamps();
        });

        Schema::create('fleet_fuel_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers')->nullOnDelete();
            $table->date('fuel_date')->index();
            $table->string('fuel_station')->nullable()->index();
            $table->string('fuel_type')->index();
            $table->decimal('liters', 10, 2);
            $table->decimal('rate_per_liter', 10, 2);
            $table->decimal('total_amount', 12, 2);
            $table->decimal('previous_odometer', 12, 2)->default(0);
            $table->decimal('odometer_reading', 12, 2);
            $table->decimal('distance', 12, 2)->default(0);
            $table->decimal('average_km_per_liter', 10, 2)->default(0);
            $table->boolean('is_abnormal_average')->default(false)->index();
            $table->string('receipt_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->date('maintenance_date')->index();
            $table->decimal('odometer_reading', 12, 2)->default(0);
            $table->string('maintenance_type')->index();
            $table->string('workshop')->nullable()->index();
            $table->text('description')->nullable();
            $table->decimal('parts_cost', 12, 2)->default(0);
            $table->decimal('labour_cost', 12, 2)->default(0);
            $table->decimal('total_cost', 12, 2)->default(0);
            $table->decimal('next_service_odometer', 12, 2)->nullable()->index();
            $table->date('next_service_date')->nullable()->index();
            $table->string('status')->default('Pending')->index();
            $table->foreignId('mechanic_driver_id')->nullable()->constrained('fleet_drivers')->nullOnDelete();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->boolean('is_overdue')->default(false)->index();
            $table->unsignedInteger('downtime_minutes')->nullable();
            $table->string('invoice_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->nullable()->constrained('fleet_vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers')->nullOnDelete();
            $table->date('expense_date')->index();
            $table->string('category')->index();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('source_type')->nullable()->index();
            $table->unsignedBigInteger('source_id')->nullable()->index();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_expenses');
        Schema::dropIfExists('fleet_maintenance_logs');
        Schema::dropIfExists('fleet_fuel_logs');
        Schema::dropIfExists('fleet_vehicle_assignments');
    }
};
