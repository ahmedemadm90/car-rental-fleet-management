<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars')->onDelete('cascade');
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->enum('rental_type', ['travel', 'wedding', 'daily']); // سفر، زفاف، يومي
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', ['pending', 'confirmed', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
        });

        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained('cars')->onDelete('cascade');
            $table->string('title'); // اسم المصروف (صيانة، زيت، إطارات، ترخيص)
            $table->decimal('amount', 10, 2); // التكلفة
            $table->date('expense_date'); // تاريخ المصروف
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('bookings');
    }
};
