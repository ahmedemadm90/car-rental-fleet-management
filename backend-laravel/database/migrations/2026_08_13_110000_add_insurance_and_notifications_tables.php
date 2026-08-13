<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table): void {
            $table->date('insurance_expires_at')->nullable()->after('next_inspection_date');
            $table->string('insurance_provider')->nullable()->after('insurance_expires_at');
            $table->string('insurance_policy_number')->nullable()->after('insurance_provider');
        });

        Schema::create('push_tokens', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token', 512)->unique();
            $table->string('platform', 20)->default('unknown');
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'platform']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('push_tokens');

        Schema::table('cars', function (Blueprint $table): void {
            $table->dropColumn(['insurance_expires_at', 'insurance_provider', 'insurance_policy_number']);
        });
    }
};
