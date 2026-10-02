<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Issues and revokes Sanctum personal access tokens for API clients
 * (mobile apps, scripts, Postman…). Send the token as: Authorization: Bearer <token>
 */
class TokenController extends Controller
{
    /**
     * POST /api/tokens: exchange email + password for a token.
     */
    public function store(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
        ]);

        $user = User::where('email', $credentials['email'])->first();

        // Same error for "no such user" and "wrong password", so emails can't be guessed
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        // Only the hash is stored in the database; the plain token is shown once, here
        $token = $user->createToken($credentials['device_name'])->plainTextToken;

        return response()->json(['token' => $token, 'token_type' => 'Bearer'], 201);
    }

    /**
     * DELETE /api/tokens/current: revoke the token used for this request ("log out").
     */
    public function destroy(Request $request): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->noContent();
    }
}
