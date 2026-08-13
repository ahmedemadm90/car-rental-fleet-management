<?php

namespace App\Http\Controllers;

use App\Models\Car;
use Illuminate\Http\Request;

class CarController extends Controller {
    public function index() {
        return response()->json(Car::with(['bookings', 'expenses'])->get());
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'name' => 'required|string',
            'model' => 'required|string',
            'plate_number' => 'required|string|unique:cars',
            'daily_rate' => 'required|numeric',
        ]);

        $car = Car::create($validated);
        return response()->json(['message' => 'Car created successfully', 'data' => $car], 201);
    }

    public function show(Car $car) {
        return response()->json($car->load(['bookings', 'expenses']));
    }

    public function update(Request $request, Car $car) {
        $car->update($request->all());
        return response()->json(['message' => 'Car updated successfully', 'data' => $car]);
    }

    public function destroy(Car $car) {
        $car->delete();
        return response()->json(['message' => 'Car deleted successfully']);
    }
}
