<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;

class BookingController extends ApiController
{
    public function myBookings(Request $request): JsonResponse
    {
        return $this->success($request->user()->bookings()->with('car.rentalShop')->latest()->paginate(20));
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'car_id' => ['required', 'integer', 'exists:cars,id'],
            'rental_type' => ['required', Rule::in(['daily', 'travel', 'wedding'])],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'pickup_location' => ['nullable', 'string', 'max:255'],
            'dropoff_location' => ['nullable', 'string', 'max:255'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'customer_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $car = Car::with('rentalShop')->findOrFail($validated['car_id']);
        abort_unless($car->rentalShop->is_active && $car->isAvailableBetween($validated['start_date'], $validated['end_date']), 422, 'This car is not available for the requested dates.');

        $numberOfDays = Carbon::parse($validated['start_date'])->diffInDays(Carbon::parse($validated['end_date'])) + 1;
        $rate = $validated['rental_type'] === 'wedding' && $car->wedding_rate ? $car->wedding_rate : $car->daily_rate;

        $booking = Booking::create([
            ...$validated,
            'customer_id' => $request->user()->id,
            'daily_rate' => $rate,
            'total_amount' => $rate * $numberOfDays,
            'deposit_amount' => $validated['deposit_amount'] ?? 0,
        ]);

        return $this->success($booking->load('car.rentalShop'), 201);
    }

    public function cancel(Request $request, int $booking): JsonResponse
    {
        $reservation = $request->user()->bookings()->findOrFail($booking);
        abort_unless(in_array($reservation->status, ['pending', 'confirmed']), 422, 'This booking can no longer be cancelled.');
        $reservation->update(['status' => 'cancelled']);

        return $this->success($reservation->fresh());
    }

    public function ownerIndex(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'owner' || $request->user()->role === 'admin', 403);

        return $this->success(Booking::query()
            ->with(['car.rentalShop', 'customer:id,name,email,phone'])
            ->when($request->user()->role !== 'admin', fn ($query) => $query->whereHas('car.rentalShop', fn ($shop) => $shop->where('owner_id', $request->user()->id)))
            ->latest()
            ->paginate(30));
    }

    public function updateStatus(Request $request, int $booking): JsonResponse
    {
        $reservation = Booking::with('car.rentalShop')->findOrFail($booking);
        abort_unless($request->user()->role === 'admin' || $reservation->car->rentalShop->owner_id === $request->user()->id, 403);

        $validated = $request->validate([
            'status' => ['required', Rule::in(['confirmed', 'active', 'completed', 'cancelled', 'rejected'])],
            'owner_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $reservation->update($validated);
        if ($validated['status'] === 'active') {
            $reservation->car->update(['status' => 'rented']);
        }
        if (in_array($validated['status'], ['completed', 'cancelled', 'rejected'])) {
            $reservation->car->update(['status' => 'available']);
        }

        return $this->success($reservation->fresh('car'));
    }
}
