<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Car;
use App\Models\RentalShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

abstract class ApiController extends Controller
{
    protected function ownerShop(Request $request, int $shopId): RentalShop
    {
        abort_unless($request->user()->role === 'owner' || $request->user()->role === 'admin', 403, 'Owner access is required.');

        return RentalShop::query()
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->findOrFail($shopId);
    }

    protected function ownerCar(Request $request, int $carId): Car
    {
        abort_unless($request->user()->role === 'owner' || $request->user()->role === 'admin', 403, 'Owner access is required.');

        return Car::query()
            ->when($request->user()->role !== 'admin', fn ($query) => $query->whereHas('rentalShop', fn ($shop) => $shop->where('owner_id', $request->user()->id)))
            ->findOrFail($carId);
    }

    protected function success(mixed $data, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $data], $status);
    }
}
