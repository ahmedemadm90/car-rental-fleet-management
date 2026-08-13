<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller {
    public function index() {
        return response()->json(Expense::with('car')->get());
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'title' => 'required|string',
            'amount' => 'required|numeric',
            'expense_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        $expense = Expense::create($validated);
        return response()->json(['message' => 'Expense recorded successfully', 'data' => $expense], 201);
    }
}
