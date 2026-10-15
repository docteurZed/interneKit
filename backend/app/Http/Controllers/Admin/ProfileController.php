<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdatePasswordRequest;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Support\Facades\Auth;

class ProfileController extends Controller
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function update(User $user, UserRequest $request)
    {
        // $user = Auth::user();

        $user = $this->service->update($user, $request->validated());

        return new UserResource($user);
    }

    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();

        $user = $this->service->updatePassword($user, $request->validated());

        return new UserResource($user);
    }
}
