<?php

namespace App\Http\Controllers\Api;

use App\Models\Booking;
use App\Models\Car;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class DashboardController extends ApiController
{
    public function summary(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'owner' || $request->user()->role === 'admin', 403);

        $ownedCars = Car::query()->when(
            $request->user()->role !== 'admin',
            fn ($query) => $query->whereHas('rentalShop', fn ($shop) => $shop->where('owner_id', $request->user()->id))
        );
        $carIds = (clone $ownedCars)->pluck('id');
        $monthStart = now()->startOfMonth();

        $revenue = Booking::whereIn('car_id', $carIds)
            ->whereIn('status', ['confirmed', 'active', 'completed'])
            ->whereDate('start_date', '>=', $monthStart)
            ->sum('total_amount');
        $expenses = Expense::whereIn('car_id', $carIds)
            ->whereDate('expense_date', '>=', $monthStart)
            ->sum('amount');
        $maintenanceDue = (clone $ownedCars)
            ->where(function ($query): void {
                $query->whereNotNull('next_oil_change_at_km')
                    ->whereColumn('current_odometer_km', '>=', 'next_oil_change_at_km')
                    ->orWhere(function ($dateQuery): void {
                        $dateQuery->whereNotNull('next_inspection_date')
                            ->whereDate('next_inspection_date', '<=', Carbon::now()->addDays(14));
                    });
            })
            ->get(['id', 'make', 'model', 'plate_number', 'current_odometer_km', 'next_oil_change_at_km', 'next_inspection_date']);

        return $this->success([
            'month' => now()->format('Y-m'),
            'fleet_size' => $ownedCars->count(),
            'available_cars' => (clone $ownedCars)->where('status', 'available')->count(),
            'rented_cars' => (clone $ownedCars)->where('status', 'rented')->count(),
            'month_revenue' => (float) $revenue,
            'month_expenses' => (float) $expenses,
            'month_net' => (float) $revenue - (float) $expenses,
            'pending_bookings' => Booking::whereIn('car_id', $carIds)->where('status', 'pending')->count(),
            'maintenance_due' => $maintenanceDue,
        ]);
    }
}
