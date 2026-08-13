<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\RentalShop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_search_and_create_a_booking_for_an_available_car(): void
    {
        $owner = User::factory()->create(['role' => 'owner']);
        $shop = RentalShop::create([
            'owner_id' => $owner->id,
            'name' => 'Cairo Mobility',
            'city' => 'Cairo',
            'phone' => '01000000000',
        ]);
        $car = Car::create([
            'rental_shop_id' => $shop->id,
            'make' => 'Toyota',
            'model' => 'Corolla',
            'year' => 2024,
            'plate_number' => 'ABC-123',
            'daily_rate' => 1500,
            'current_odometer_km' => 20000,
            'next_oil_change_at_km' => 30000,
            'status' => 'available',
        ]);
        $customer = User::factory()->create(['role' => 'customer']);

        $this->getJson('/api/v1/cars?city=Cairo')
            ->assertOk()
            ->assertJsonPath('data.data.0.id', $car->id);

        Sanctum::actingAs($customer);
        $payload = [
            'car_id' => $car->id,
            'rental_type' => 'travel',
            'start_date' => now()->addDays(2)->toDateString(),
            'end_date' => now()->addDays(4)->toDateString(),
            'pickup_location' => 'Cairo Airport',
        ];

        $this->postJson('/api/v1/bookings', $payload)
            ->assertCreated()
            ->assertJsonPath('data.total_amount', '4500.00');

        $this->postJson('/api/v1/bookings', $payload)
            ->assertUnprocessable()
            ->assertJsonPath('message', 'This car is not available for the requested dates.');
    }
}
