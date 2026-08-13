<?php

namespace App\Http\Controllers\Api;

use App\Models\RentalShop;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RentalShopController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->role === 'owner' || $request->user()->role === 'admin', 403);

        $shops = RentalShop::query()
            ->when($request->user()->role !== 'admin', fn ($query) => $query->where('owner_id', $request->user()->id))
            ->withCount('cars')
            ->get();

        return $this->success($shops);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        abort_unless($request->user()->role === 'owner' || $request->user()->role === 'admin', 403);
        $validated['owner_id'] = $request->user()->id;

        return $this->success(RentalShop::create($validated), 201);
    }

    public function update(Request $request, int $shop): JsonResponse
    {
        $rentalShop = $this->ownerShop($request, $shop);
        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:150'],
            'city' => ['sometimes', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:30'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $rentalShop->update($validated);

        return $this->success($rentalShop->fresh());
    }
}
