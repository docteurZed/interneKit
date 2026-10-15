<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Service extends Model
{
    protected $fillable = [
        'name',
        'hopital_id',
    ];

    public function hopital(): BelongsTo
    {
        return $this->belongsTo(Hopital::class);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function gardes(): HasMany
    {
        return $this->hasMany(Garde::class);
    }

    public function internes(): HasManyThrough
    {
        return $this->hasManyThrough(
            Interne::class,
            Affectation::class,
            'service_id',
            'id',
            'id',
            'interne_id'
        );
    }
}
