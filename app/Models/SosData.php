<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SosData extends Model
{
    use HasFactory;

    protected $table = 'sos_data';

    protected $fillable = [
        'trip_id',
        'encrypted_payload',
    ];

    public function trip()
    {
        return $this->belongsTo(Trip::class);
    }
}
