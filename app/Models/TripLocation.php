<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TripLocation extends Model
{
     protected $fillable = ['trip_id', 'encrypted_data'];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
