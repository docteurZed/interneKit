<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Http\Resources\Admin\UserResource;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return UserResource::collection($this->service->getAll());
    }

    public function store(UserRequest $request)
    {
        $user = $this->service->create($request->validated());

        return new UserResource($user);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(UserRequest $request, User $user)
    {
        $user = $this->service->update($user, $request->validated());

        return new UserResource($user);
    }

    public function destroy(User $user)
    {
        $auth = Auth::user();

        if ($auth->id === $user->id) {
            abort(403, 'Vous ne pouvez pas supprimer votre propre compte.');
        }

        $this->service->delete($user);

        return response()->noContent();
    }
}
