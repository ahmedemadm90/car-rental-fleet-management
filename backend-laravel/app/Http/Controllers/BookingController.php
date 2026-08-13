<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller {
    public function index() {
        return response()->json(Booking::with('car')->get());
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'rental_type' => 'required|in:travel,wedding,daily',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'total_amount' => 'required|numeric',
        ]);

        $booking = Booking::create($validated);
        return response()->json(['message' => 'Booking created successfully', 'data' => $booking], 201);
    }
}
