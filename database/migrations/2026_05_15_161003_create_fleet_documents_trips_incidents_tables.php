<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_vehicle_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->string('document_type')->index();
            $table->string('document_no')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->index();
            $table->string('attachment_path')->nullable();
            $table->string('status')->default('valid')->index();
            $table->timestamps();
        });

        Schema::create('fleet_trip_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->constrained('fleet_drivers')->cascadeOnDelete();
            $table->date('trip_date')->index();
            $table->string('from_location');
            $table->string('to_location');
            $table->string('purpose')->nullable();
            $table->decimal('start_odometer', 12, 2);
            $table->decimal('end_odometer', 12, 2);
            $table->decimal('distance', 12, 2);
            $table->string('passenger_department')->nullable();
            $table->string('approved_by')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
        });

        Schema::create('fleet_tyres_batteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->string('item_type')->index(); // tyre or battery
            $table->string('brand')->nullable();
            $table->string('serial_no')->nullable()->index();
            $table->date('installed_at')->nullable()->index();
            $table->decimal('installed_odometer', 12, 2)->nullable();
            $table->date('removed_at')->nullable();
            $table->decimal('removed_odometer', 12, 2)->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('status')->default('Active')->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fleet_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers')->nullOnDelete();
            $table->date('incident_date')->index();
            $table->string('incident_type')->index();
            $table->string('severity')->default('minor')->index();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->decimal('estimated_cost', 12, 2)->default(0);
            $table->decimal('settled_cost', 12, 2)->default(0);
            $table->string('status')->default('Open')->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fleet_challans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vehicle_id')->constrained('fleet_vehicles')->cascadeOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('fleet_drivers')->nullOnDelete();
            $table->date('challan_date')->index();
            $table->string('challan_no')->nullable()->index();
            $table->string('violation_type')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('status')->default('Unpaid')->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('fleet_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_settings');
        Schema::dropIfExists('fleet_challans');
        Schema::dropIfExists('fleet_incidents');
        Schema::dropIfExists('fleet_tyres_batteries');
        Schema::dropIfExists('fleet_trip_logs');
        Schema::dropIfExists('fleet_vehicle_documents');
    }
};
