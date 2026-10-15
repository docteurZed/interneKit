<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Affectation extends Model
{
    protected $fillable = [
        'interne_id',
        'service_id',
        'periode_rotation_id',
    ];

    public function interne(): BelongsTo
    {
        return $this->belongsTo(Interne::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function periodeRotation(): BelongsTo
    {
        return $this->belongsTo(PeriodeRotation::class);
    }
}
