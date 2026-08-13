<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_shops', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('city');
            $table->string('address')->nullable();
            $table->string('phone', 30);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('cars', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rental_shop_id')->constrained()->cascadeOnDelete();
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->string('plate_number')->unique();
            $table->string('color')->nullable();
            $table->unsignedInteger('seats')->default(4);
            $table->decimal('daily_rate', 12, 2);
            $table->decimal('wedding_rate', 12, 2)->nullable();
            $table->unsignedInteger('current_odometer_km')->default(0);
            $table->unsignedInteger('oil_change_interval_km')->default(10000);
            $table->unsignedInteger('next_oil_change_at_km')->nullable();
            $table->date('next_inspection_date')->nullable();
            $table->enum('status', ['available', 'rented', 'maintenance', 'inactive'])->default('available');
            $table->text('features')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
            $table->index(['rental_shop_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
        Schema::dropIfExists('rental_shops');
    }
};
