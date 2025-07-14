<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Validation\Rules\Password as PasswordWeakness;

class AuthController extends Controller
{
    /**
     * register
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordWeakness::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = $user->createToken('access_token')->plainTextToken;

        event(new Registered($user));

        return response()->json(['token' => $token], 201);
    }

    /**
     * login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Credenciais inválidas'], 401);
        }

        $accessToken = $user->createToken('access_token', ['access'])->plainTextToken;
        $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        return response()->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
        ]);
    }

    /**
     * refresh token
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        $request->user()->currentAccessToken()->delete();

        $newAccessToken = $user->createToken('access_token', ['*'], Carbon::now()->addMinutes(60))->plainTextToken;
        $newRefreshToken = $user->createToken('refresh_token', ['refresh'], Carbon::now()->addDays(7))->plainTextToken;

        return response()->json([
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
        ]);
    }

    /**
     * reset password
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'token' => ['required'],
            'password' => [
                'required',
                'string',
                'confirmed',
                PasswordWeakness::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? response()->json(['message' => 'Senha redefinida com sucesso.'])
            : response()->json(['message' => 'Erro ao redefinir a senha.'], 422);
    }

    /**
     * forgot password
     */
    public function forgotPassword(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'exists:users,email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? response()->json(['message' => 'Link de redefinição enviado para o email.'])
            : response()->json(['message' => 'Não foi possível enviar o link.'], 500);
    }

    /**
     * logout
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * logout all devices
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logout realizado em todos os dispositivos.']);
    }

    /**
     * message to verify email
     */
    public function verificationNotice()
    {
        return response()->json(['message' => 'Seu e-mail ainda não foi verificado.']);
    }

    /**
     * verify email
     */
    public function verifyEmail(EmailVerificationRequest $request)
    {
        $request->fulfill();

        return response()->json(['message' => 'E-mail verificado com sucesso.']);
    }

    /**
     * resend email verification
     */
    public function resendVerification(Request $request)
    {
        if ($request->user()->hasVerifiedEmail()) {
            return response()->json(['message' => 'E-mail já verificado.']);
        }

        $request->user()->sendEmailVerificationNotification();

        return response()->json(['message' => 'Link de verificação enviado.']);
    }
}
