<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthService;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class LoginController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    /**
     * Handle user login request.
     */
    public function __invoke(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                email: $request->input('email'),
                password: $request->input('password'),
                remember: $request->boolean('remember', false)
            );

            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'user' => new UserResource($result['user']->load('roles')),
                    'token' => $result['token'],
                    'token_type' => 'Bearer',
                ],
            ], Response::HTTP_OK);
        } catch (AuthenticationException $e) {
            return response()->json([
                'message' => 'Authentication failed',
                'errors' => [
                    'credentials' => [$e->getMessage()],
                ],
            ], Response::HTTP_UNAUTHORIZED);
        }
    }
}
