<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateGardeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'type_garde_id' => ['sometimes', 'required', 'integer', 'exists:types_garde,id'],
            'service_id' => ['sometimes', 'required', 'integer', 'exists:services,id'],
            'date_garde' => ['sometimes', 'required', 'date'],
            'interne_ids' => ['sometimes', 'array', 'min:1'],
            'interne_ids.*' => ['integer', 'exists:internes,id', 'distinct'],
        ];
    }
}
