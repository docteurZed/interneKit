<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        return $user?->role === 'admin';
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:255'],
            'phone'       => ['nullable', 'string', 'max:20'],
            'email'       => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user'))
            ],
            'role'    => request()->isMethod('post')
                ? ['required', 'string', 'in:admin,user']
                : ['sometimes', 'string', 'in:admin,user'],

            'is_active'   => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'  => 'nom',
            'phone'      => 'numéro de téléphone',
            'email'      => 'adresse email',
            'role'  => 'rôle',
            'is_active'  => 'statut actif',
        ];
    }
}
