<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->string('provider')->default('paymob')->after('status');
            $table->string('provider_payment_id')->nullable()->unique()->after('provider');
            $table->string('checkout_url', 2048)->nullable()->after('provider_payment_id');
            $table->json('gateway_payload')->nullable()->after('checkout_url');
            $table->timestamp('expires_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->dropColumn(['provider', 'provider_payment_id', 'checkout_url', 'gateway_payload', 'expires_at']);
        });
    }
};
