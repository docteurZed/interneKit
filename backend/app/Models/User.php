<?php

namespace App\Models;

use App\Notifications\CustomResetPassword;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $appends = [
        'initials'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomResetPassword($token));
    }

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
}
