<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    // redirect to google
    public function redirectToGoogle()
    {
        /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
        $driver = Socialite::driver('google');
        return $driver->stateless()->redirect();
    }

    // callback from google
    public function handleGoogleCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\GoogleProvider $driver */
            $driver = Socialite::driver('google');
            $googleUser = $driver->stateless()->user();

            $email = $googleUser->getEmail();
            $name = $googleUser->getName();
            $googleId = $googleUser->getId();

            if (empty($email)) {
                return response()->json([
                    'message' => 'Email ausente ou inválido',
                ], 422);
            }

            if (empty($name)) {
                return response()->json([
                    'message' => 'Nome ausente ou inválido',
                ], 422);
            }

            if (empty($googleId)) {
                return response()->json([
                    'message' => 'ID do Google ausente ou inválido',
                ], 422);
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'google_id' => $googleId,
                    'password' => bcrypt(Str::random(24)),
                ]
            );

            Auth::login($user);
            $token = $user->createToken('google-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // redirect to facebook
    public function redirectToFacebook()
    {
        /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
        $driver = Socialite::driver('facebook');
        return $driver->stateless()->redirect();
    }

    // callback from google
    public function handleFacebookCallback()
    {
        try {
            /** @var \Laravel\Socialite\Two\FacebookProvider $driver */
            $driver = Socialite::driver('facebook');
            $facebookUser = $driver->stateless()->user();

            $email = $facebookUser->getEmail();
            $name = $facebookUser->getName();
            $facebookId = $facebookUser->getId();

            if (empty($email)) {
                return response()->json([
                    'message' => 'Email ausente ou inválido',
                ], 422);
            }

            if (empty($name)) {
                return response()->json([
                    'message' => 'Nome ausente ou inválido',
                ], 422);
            }

            if (empty($facebookId)) {
                return response()->json([
                    'message' => 'ID do Facebook ausente ou inválido',
                ], 422);
            }

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'facebook_id' => $facebookId,
                    'password' => bcrypt(Str::random(24)),
                ]
            );

            Auth::login($user);
            $token = $user->createToken('facebook-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
