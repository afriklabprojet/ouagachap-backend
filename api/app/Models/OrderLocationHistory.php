<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderLocationHistory extends Model
{
    public $timestamps = false;

    protected $table = 'order_location_history';

    protected $fillable = [
        'order_id',
        'courier_id',
        'latitude',
        'longitude',
        'heading',
        'speed',
        'accuracy',
        'recorded_at',
    ];

    protected $casts = [
        'latitude'    => 'float',
        'longitude'   => 'float',
        'heading'     => 'float',
        'speed'       => 'float',
        'accuracy'    => 'float',
        'recorded_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function courier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'courier_id');
    }
}
