<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Car extends Model {
    use HasFactory;

    protected $fillable = [
        'name', 'model', 'plate_number', 'daily_rate', 'status', 'last_oil_change', 'oil_change_km'
    ];

    public function bookings() {
        return $this->hasMany(Booking::class);
    }

    public function expenses() {
        return $this->hasMany(Expense::class);
    }
}
