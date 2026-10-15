<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'hopital_id' => ['required', 'integer', 'exists:hopitaux,id'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('services', 'name')->where(fn ($query) => $query->where('hopital_id', $this->input('hopital_id'))),
            ],
        ];
    }
}
