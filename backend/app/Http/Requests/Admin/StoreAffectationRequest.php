<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreAffectationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'interne_id' => ['required', 'integer', 'exists:internes,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'periode_rotation_id' => ['required', 'integer', 'exists:periodes_rotation,id'],
        ];
    }
}
