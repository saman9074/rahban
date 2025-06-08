<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripGuardian extends Model
{
     protected $fillable = ['trip_id', 'name', 'phone_number'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
