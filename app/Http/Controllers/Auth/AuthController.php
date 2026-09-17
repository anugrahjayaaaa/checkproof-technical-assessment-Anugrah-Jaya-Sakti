<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\Common\ErrorResource;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login(
            $request->validated()
        );

        if (! $result['success']) {
            return (new ErrorResource([
                'message' => $result['message'],
            ]))
                ->response()
                ->setStatusCode(401);
        }

        return (new AuthResource($result))
            ->additional([
                'message' => 'Login successful.',
            ])
            ->response()
            ->setStatusCode(200);
    }
}
