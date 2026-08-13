<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\RentalShop;
use App\Models\User;
use App\Notifications\VehicleReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class VehicleReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_an_insurance_expiry_reminder_only_once_per_day(): void
    {
        Notification::fake();

        $owner = User::factory()->create(['role' => 'owner']);
        $shop = RentalShop::create([
            'owner_id' => $owner->id,
            'name' => 'Fleet Hub',
            'city' => 'Cairo',
            'phone' => '01000000000',
        ]);
        Car::create([
            'rental_shop_id' => $shop->id,
            'make' => 'Toyota',
            'model' => 'Yaris',
            'year' => 2024,
            'plate_number' => 'INS-123',
            'daily_rate' => 1000,
            'insurance_expires_at' => now()->addDays(7),
            'current_odometer_km' => 1000,
            'next_oil_change_at_km' => 10000,
        ]);

        $this->artisan('notifications:send-vehicle-reminders')->assertSuccessful();
        Notification::assertSentTo($owner, VehicleReminder::class, function (VehicleReminder $notification): bool {
            return true;
        });

        Notification::fake();
        $this->artisan('notifications:send-vehicle-reminders')->assertSuccessful();
        Notification::assertNothingSent();
    }
}
