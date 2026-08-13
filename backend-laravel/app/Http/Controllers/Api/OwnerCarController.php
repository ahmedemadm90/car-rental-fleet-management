<?php

namespace App\Http\Controllers\Api;

use App\Models\Car;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OwnerCarController extends ApiController
{
    public function index(Request $request, int $shop): JsonResponse
    {
        $rentalShop = $this->ownerShop($request, $shop);

        return $this->success($rentalShop->cars()->withCount(['bookings', 'expenses', 'maintenanceRecords'])->paginate(20));
    }

    public function store(Request $request, int $shop): JsonResponse
    {
        $rentalShop = $this->ownerShop($request, $shop);
        $validated = $this->validatedCar($request);
        $validated['rental_shop_id'] = $rentalShop->id;
        $validated['next_oil_change_at_km'] ??= $validated['current_odometer_km'] + ($validated['oil_change_interval_km'] ?? 10000);

        return $this->success(Car::create($validated), 201);
    }

    public function show(Request $request, int $car): JsonResponse
    {
        return $this->success($this->ownerCar($request, $car)->load(['rentalShop', 'maintenanceRecords', 'expenses']));
    }

    public function update(Request $request, int $car): JsonResponse
    {
        $vehicle = $this->ownerCar($request, $car);
        $vehicle->update($this->validatedCar($request, true));

        return $this->success($vehicle->fresh());
    }

    public function destroy(Request $request, int $car): JsonResponse
    {
        $vehicle = $this->ownerCar($request, $car);
        abort_if($vehicle->bookings()->whereIn('status', ['pending', 'confirmed', 'active'])->exists(), 422, 'A car with active bookings cannot be deleted.');
        $vehicle->delete();

        return response()->json([], 204);
    }

    private function validatedCar(Request $request, bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes' : 'required';

        return $request->validate([
            'make' => [$prefix, 'string', 'max:100'],
            'model' => [$prefix, 'string', 'max:100'],
            'year' => [$prefix, 'integer', 'between:1990,2100'],
            'plate_number' => [$prefix, 'string', 'max:50', Rule::unique('cars', 'plate_number')->ignore($request->route('car'))],
            'color' => ['nullable', 'string', 'max:50'],
            'seats' => ['sometimes', 'integer', 'between:1,15'],
            'daily_rate' => [$prefix, 'numeric', 'min:0'],
            'wedding_rate' => ['nullable', 'numeric', 'min:0'],
            'current_odometer_km' => ['sometimes', 'integer', 'min:0'],
            'oil_change_interval_km' => ['sometimes', 'integer', 'min:1000'],
            'next_oil_change_at_km' => ['nullable', 'integer', 'min:0'],
            'next_inspection_date' => ['nullable', 'date'],
            'insurance_expires_at' => ['nullable', 'date'],
            'insurance_provider' => ['nullable', 'string', 'max:120'],
            'insurance_policy_number' => ['nullable', 'string', 'max:120'],
            'status' => ['sometimes', Rule::in(['available', 'rented', 'maintenance', 'inactive'])],
            'features' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'url', 'max:2048'],
        ]);
    }
}
