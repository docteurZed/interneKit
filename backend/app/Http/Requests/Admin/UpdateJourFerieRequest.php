<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateJourFerieRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date', Rule::unique('jours_feries', 'date')->ignore($this->route('jourFerie'))],
            'name' => ['required', 'string', 'max:255'],
        ];
    }
}
