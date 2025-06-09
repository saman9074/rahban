<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    protected $fillable = [
    'user_id',
    'vehicle_info',
    'plate_photo_path',
    'status',
    'share_token',
    'expires_at',
    'deletable_at',
    ];

    protected function casts(): array
    {
        return [
            'vehicle_info' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function guardians()
    {
        return $this->hasMany(TripGuardian::class);
    }

    public function locations()
    {
        return $this->hasMany(TripLocation::class);
    }

    public function shortUrl() 
    {
        return $this->hasOne(ShortUrl::class);
    }
}