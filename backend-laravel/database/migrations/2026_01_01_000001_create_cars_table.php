<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم السيارة مثلاً تويوتا كوريلا
            $table->string('model'); // موديل السيارة
            $table->string('plate_number'); // رقم اللوحة
            $table->decimal('daily_rate', 10, 2); // سعر الإيجار اليومي
            $table->enum('status', ['available', 'rented', 'maintenance'])->default('available');
            $table->date('last_oil_change')->nullable(); // تاريخ آخر تغيير زيت
            $table->integer('oil_change_km')->nullable(); // عداد تغيير الزيت القادم
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('cars');
    }
};
