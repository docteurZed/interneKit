<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TypeGarde extends Model
{
    protected $table = 'types_garde';

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'decalage_end',
    ];

    public function gardes(): HasMany
    {
        return $this->hasMany(Garde::class);
    }
}
