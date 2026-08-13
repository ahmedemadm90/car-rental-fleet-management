<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Car;
use App\Models\Expense;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function dashboard(Request $request)
    {
        $this->ensureOwner($request);
        $carIds = $this->carIds($request);

        $stats = [
            'fleet' => $carIds->count(),
            'available' => Car::whereIn('id', $carIds)->where('status', 'available')->count(),
            'rented' => Car::whereIn('id', $carIds)->where('status', 'rented')->count(),
            'revenue' => Booking::whereIn('car_id', $carIds)
                ->whereIn('status', ['confirmed', 'active', 'completed'])
                ->whereDate('start_date', '>=', now()->startOfMonth())
                ->sum('total_amount'),
            'expenses' => Expense::whereIn('car_id', $carIds)
                ->whereDate('expense_date', '>=', now()->startOfMonth())
                ->sum('amount'),
            'pending' => Booking::whereIn('car_id', $carIds)->where('status', 'pending')->count(),
        ];

        $recentBookings = Booking::with(['car.rentalShop', 'customer'])
            ->whereIn('car_id', $carIds)
            ->latest()
            ->take(5)
            ->get();
        $maintenanceDue = Car::whereIn('id', $carIds)
            ->where(function ($query): void {
                $query->whereColumn('current_odometer_km', '>=', 'next_oil_change_at_km')
                    ->orWhereDate('next_inspection_date', '<=', now()->addDays(14));
            })
            ->get();

        return view('dashboard.index', compact('stats', 'recentBookings', 'maintenanceDue'));
    }

    public function fleet(Request $request)
    {
        $this->ensureOwner($request);
        $cars = Car::with('rentalShop')
            ->whereIn('id', $this->carIds($request))
            ->withCount(['bookings', 'expenses', 'maintenanceRecords'])
            ->latest()
            ->paginate(15);

        return view('dashboard.fleet', compact('cars'));
    }

    public function bookings(Request $request)
    {
        $this->ensureOwner($request);
        $bookings = Booking::with(['car.rentalShop', 'customer'])
            ->whereIn('car_id', $this->carIds($request))
            ->latest()
            ->paginate(15);

        return view('dashboard.bookings', compact('bookings'));
    }

    private function ensureOwner(Request $request): void
    {
        abort_unless($request->user()?->role === 'owner' || $request->user()?->role === 'admin', 403);
    }

    private function carIds(Request $request)
    {
        return Car::query()
            ->when($request->user()->role !== 'admin', fn ($query) => $query->whereHas('rentalShop', fn ($shop) => $shop->where('owner_id', $request->user()->id)))
            ->pluck('id');
    }
}
