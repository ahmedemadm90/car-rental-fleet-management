<?php

namespace App\Http\Controllers\Api;

use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicCarController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city' => ['nullable', 'string', 'max:100'],
            'rental_type' => ['nullable', Rule::in(['daily', 'travel', 'wedding'])],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_daily_rate' => ['nullable', 'numeric', 'min:0'],
            'seats' => ['nullable', 'integer', 'min:1'],
        ]);

        $cars = Car::query()
            ->with('rentalShop:id,name,city,phone')
            ->where('status', 'available')
            ->whereHas('rentalShop', function ($query) use ($validated): void {
                $query->where('is_active', true)
                    ->when($validated['city'] ?? null, fn ($q, $city) => $q->where('city', 'like', "%{$city}%"));
            })
            ->when($validated['max_daily_rate'] ?? null, fn ($q, $rate) => $q->where('daily_rate', '<=', $rate))
            ->when($validated['seats'] ?? null, fn ($q, $seats) => $q->where('seats', '>=', $seats))
            ->when(($validated['start_date'] ?? null) && ($validated['end_date'] ?? null), function ($query) use ($validated): void {
                $query->whereDoesntHave('bookings', function ($bookingQuery) use ($validated): void {
                    $bookingQuery->whereIn('status', ['pending', 'confirmed', 'active'])
                        ->whereDate('start_date', '<=', $validated['end_date'])
                        ->whereDate('end_date', '>=', $validated['start_date']);
                });
            })
            ->orderBy('daily_rate')
            ->paginate(15);

        return $this->success($cars);
    }

    public function show(Car $car): JsonResponse
    {
        abort_unless($car->rentalShop->is_active, 404);

        return $this->success($car->load('rentalShop:id,name,city,address,phone,description'));
    }
}
