<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Garde extends Model
{
    protected $fillable = [
        'type_garde_id',
        'service_id',
        'date_garde',
        'start',
        'end',
    ];

    protected function casts(): array
    {
        return [
            'date_garde' => 'date',
            'start' => 'datetime',
            'end' => 'datetime',
        ];
    }

    public function typeGarde(): BelongsTo
    {
        return $this->belongsTo(TypeGarde::class);
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    public function internes(): BelongsToMany
    {
        return $this->belongsToMany(Interne::class, 'garde_interne')
            ->withTimestamps();
    }
}
