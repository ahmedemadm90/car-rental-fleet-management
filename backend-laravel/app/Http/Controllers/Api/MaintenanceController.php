<?php

namespace App\Http\Controllers\Api;

use App\Models\MaintenanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends ApiController
{
    public function index(Request $request, int $car): JsonResponse
    {
        $vehicle = $this->ownerCar($request, $car);

        return $this->success($vehicle->maintenanceRecords()->latest('service_date')->paginate(30));
    }

    public function store(Request $request, int $car): JsonResponse
    {
        $vehicle = $this->ownerCar($request, $car);
        $validated = $request->validate([
            'type' => ['required', Rule::in(['oil_change', 'inspection', 'repair', 'tires', 'other'])],
            'title' => ['required', 'string', 'max:150'],
            'service_date' => ['required', 'date'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'next_due_odometer_km' => ['nullable', 'integer', 'min:0'],
            'next_due_date' => ['nullable', 'date'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $record = $vehicle->maintenanceRecords()->create([
            ...$validated,
            'recorded_by' => $request->user()->id,
            'cost' => $validated['cost'] ?? 0,
        ]);

        $updates = [];
        if (($validated['odometer_km'] ?? 0) > $vehicle->current_odometer_km) {
            $updates['current_odometer_km'] = $validated['odometer_km'];
        }
        if ($validated['type'] === 'oil_change') {
            $updates['next_oil_change_at_km'] = $validated['next_due_odometer_km']
                ?? (($validated['odometer_km'] ?? $vehicle->current_odometer_km) + $vehicle->oil_change_interval_km);
        }
        if ($validated['type'] === 'inspection' && ! empty($validated['next_due_date'])) {
            $updates['next_inspection_date'] = $validated['next_due_date'];
        }
        if ($updates !== []) {
            $vehicle->update($updates);
        }

        return $this->success($record, 201);
    }

    public function destroy(Request $request, int $maintenance): JsonResponse
    {
        $record = MaintenanceRecord::with('car.rentalShop')->findOrFail($maintenance);
        abort_unless($request->user()->role === 'admin' || $record->car->rentalShop->owner_id === $request->user()->id, 403);
        $record->delete();

        return response()->json([], 204);
    }
}
