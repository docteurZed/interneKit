<?php

namespace App\Models;

use Database\Factories\InterneFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Interne extends Model
{
    /** @use HasFactory<InterneFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'phone',
    ];

    protected $appends = [
        'initials'
    ];

    public function getInitialsAttribute(): string
    {
        if (empty($this->name)) {
            return '';
        }

        $words = array_filter(explode(' ', trim($this->name)));

        $letters = array_slice($words, 0, 2);

        $initials = array_map(fn($word) => strtoupper(preg_replace('/[^A-Z]/i', '', substr($word, 0, 1))), $letters);

        return implode('', $initials);
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(Affectation::class);
    }

    public function gardes(): BelongsToMany
    {
        return $this->belongsToMany(Garde::class, 'garde_interne')
            ->withTimestamps();
    }
}
