<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('category', ['fuel', 'oil_change', 'maintenance', 'tires', 'insurance', 'license', 'cleaning', 'other']);
            $table->string('title');
            $table->decimal('amount', 12, 2);
            $table->date('expense_date');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['car_id', 'expense_date']);
        });

        Schema::create('maintenance_records', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['oil_change', 'inspection', 'repair', 'tires', 'other']);
            $table->string('title');
            $table->date('service_date');
            $table->unsignedInteger('odometer_km')->nullable();
            $table->unsignedInteger('next_due_odometer_km')->nullable();
            $table->date('next_due_date')->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['car_id', 'next_due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_records');
        Schema::dropIfExists('expenses');
    }
};
