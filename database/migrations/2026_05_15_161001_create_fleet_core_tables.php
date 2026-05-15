<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_vehicle_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('fleet_drivers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('employee_code')->nullable()->index();
            $table->string('cnic')->nullable();
            $table->string('mobile_no')->nullable();
            $table->string('license_no')->nullable();
            $table->date('license_expiry_date')->nullable()->index();
            $table->string('status')->default('Active')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_type_id')->nullable()->constrained('fleet_vehicle_types')->nullOnDelete();
            $table->foreignId('assigned_driver_id')->nullable()->constrained('fleet_drivers')->nullOnDelete();
            $table->string('vehicle_no')->unique();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('chassis_no')->nullable();
            $table->string('engine_no')->nullable();
            $table->string('registration_no')->nullable()->index();
            $table->string('ownership_type')->nullable();
            $table->string('department')->nullable()->index();
            $table->decimal('current_odometer', 12, 2)->default(0);
            $table->string('fuel_type')->default('Diesel')->index();
            $table->decimal('tank_capacity', 10, 2)->nullable();
            $table->string('status')->default('Active')->index();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_vehicles');
        Schema::dropIfExists('fleet_drivers');
        Schema::dropIfExists('fleet_vehicle_types');
    }
};
