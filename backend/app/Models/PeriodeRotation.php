<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PeriodeRotation extends Model
{
    protected $table = 'periodes_rotation';

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }
}
