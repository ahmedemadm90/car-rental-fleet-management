<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Booking extends Model {
    protected $fillable = [
        'car_id', 'customer_name', 'customer_phone', 'rental_type', 'start_date', 'end_date', 'total_amount', 'status'
    ];

    public function car() {
        return $this->belongsTo(Car::class);
    }
}
