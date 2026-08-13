<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expense extends Model {
    protected $fillable = [
        'car_id', 'title', 'amount', 'expense_date', 'notes'
    ];

    public function car() {
        return $this->belongsTo(Car::class);
    }
}
