<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreGardeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'type_garde_id' => ['required', 'integer', 'exists:types_garde,id'],
            'service_id' => ['required', 'integer', 'exists:services,id'],
            'date_garde' => ['required', 'date'],
            'interne_ids' => ['required', 'array', 'min:1'],
            'interne_ids.*' => ['integer', 'exists:internes,id', 'distinct'],
        ];
    }
}
