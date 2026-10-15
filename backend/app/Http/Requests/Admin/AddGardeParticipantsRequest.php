<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class AddGardeParticipantsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'interne_ids' => ['required', 'array', 'min:1'],
            'interne_ids.*' => ['integer', 'exists:internes,id', 'distinct'],
        ];
    }
}
