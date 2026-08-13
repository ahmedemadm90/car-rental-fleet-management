<?php

namespace App\Http\Controllers\Api;

use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ExpenseController extends ApiController
{
    public function index(Request $request, int $car): JsonResponse
    {
        $vehicle = $this->ownerCar($request, $car);

        return $this->success($vehicle->expenses()->latest('expense_date')->paginate(30));
    }

    public function store(Request $request, int $car): JsonResponse
    {
        $vehicle = $this->ownerCar($request, $car);
        $validated = $request->validate([
            'category' => ['required', Rule::in(['fuel', 'oil_change', 'maintenance', 'tires', 'insurance', 'license', 'cleaning', 'other'])],
            'title' => ['required', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0'],
            'expense_date' => ['required', 'date'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $expense = $vehicle->expenses()->create([...$validated, 'recorded_by' => $request->user()->id]);

        if (($validated['odometer_km'] ?? 0) > $vehicle->current_odometer_km) {
            $vehicle->update(['current_odometer_km' => $validated['odometer_km']]);
        }

        return $this->success($expense, 201);
    }

    public function destroy(Request $request, int $expense): JsonResponse
    {
        $record = Expense::with('car.rentalShop')->findOrFail($expense);
        abort_unless($request->user()->role === 'admin' || $record->car->rentalShop->owner_id === $request->user()->id, 403);
        $record->delete();

        return response()->json([], 204);
    }
}
