<?php

namespace App\Services\Admin;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function getAll()
    {
        return User::latest()
            ->get();
    }

    public function create(array $data)
    {
        $data['password'] = Hash::make('password');

        return User::create($data);
    }

    public function update(User $user, array $data)
    {
        $user->update($data);

        return $user;
    }

    public function updatePassword(User $user, array $data)
    {
        $user->password = Hash::make($data['password']);
        $user->save();

        return $user;
    }

    public function delete(User $user)
    {
        return $user->delete();
    }
}
