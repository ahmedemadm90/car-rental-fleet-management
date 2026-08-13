<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Car extends Model
{
    protected $fillable = [
        'rental_shop_id', 'make', 'model', 'year', 'plate_number', 'color', 'seats',
        'daily_rate', 'wedding_rate', 'current_odometer_km', 'oil_change_interval_km',
        'next_oil_change_at_km', 'next_inspection_date', 'insurance_expires_at', 'insurance_provider',
        'insurance_policy_number', 'status', 'features', 'image_url',
    ];

    protected function casts(): array
    {
        return [
            'daily_rate' => 'decimal:2',
            'wedding_rate' => 'decimal:2',
            'next_inspection_date' => 'date',
            'insurance_expires_at' => 'date',
        ];
    }

    public function rentalShop(): BelongsTo
    {
        return $this->belongsTo(RentalShop::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function maintenanceRecords(): HasMany
    {
        return $this->hasMany(MaintenanceRecord::class);
    }

    public function isAvailableBetween(string $startDate, string $endDate): bool
    {
        return $this->status === 'available' && ! $this->bookings()
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->whereDate('start_date', '<=', $endDate)
            ->whereDate('end_date', '>=', $startDate)
            ->exists();
    }
}
