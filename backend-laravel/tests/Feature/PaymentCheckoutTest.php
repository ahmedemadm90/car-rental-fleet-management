<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Car;
use App\Models\RentalShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentCheckoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_is_not_started_until_the_payment_provider_is_configured(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $owner = User::factory()->create(['role' => 'owner']);
        $shop = RentalShop::create([
            'owner_id' => $owner->id,
            'name' => 'Secure Cars',
            'city' => 'Cairo',
            'phone' => '01000000000',
        ]);
        $car = Car::create([
            'rental_shop_id' => $shop->id,
            'make' => 'Kia',
            'model' => 'Cerato',
            'year' => 2024,
            'plate_number' => 'PAY-123',
            'daily_rate' => 1500,
            'current_odometer_km' => 1200,
            'next_oil_change_at_km' => 10000,
        ]);
        $booking = Booking::create([
            'car_id' => $car->id,
            'customer_id' => $customer->id,
            'rental_type' => 'wedding',
            'start_date' => now()->addDays(2),
            'end_date' => now()->addDays(2),
            'daily_rate' => 5000,
            'total_amount' => 5000,
            'deposit_amount' => 1000,
            'status' => 'pending',
        ]);

        Sanctum::actingAs($customer);
        $this->postJson("/api/v1/bookings/{$booking->id}/payment-checkout")
            ->assertStatus(503)
            ->assertJsonPath('message', 'Online payments are not configured yet.');

        $this->assertDatabaseHas('payments', [
            'booking_id' => $booking->id,
            'amount' => 1000,
            'status' => 'pending',
        ]);
    }
}
