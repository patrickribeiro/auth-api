<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * strong password
     */
    public function test_weak_password_is_rejected_on_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test',
            'email' => 'test@example.com',
            'password' => '123',
            'password_confirmation' => '123',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    /**
     * register test
     */
    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'passwordCreate12!',
            'password_confirmation' => 'passwordCreate12!',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['token']);
    }

    /**
     * register missing fields test
     */
    public function test_register_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/register', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name', 'email', 'password']);
    }

    /**
     * register email already in use test
     */
    public function test_register_fails_with_duplicate_email()
    {
        User::factory()->create(['email' => 'teste@exemplo.com']);

        $response = $this->postJson('/api/register', [
            'name' => 'Novo Usuário',
            'email' => 'teste@exemplo.com',
            'password' => 'secret123'
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    /**
     * login test
     */
    public function test_login_returns_token_on_success()
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('Senha123@'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'user@example.com',
            'password' => 'Senha123@',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'refresh_token']);
    }

    /**
     * login invalid credentials test
     */
    public function test_login_fails_with_invalid_credentials()
    {
        $response = $this->postJson('/api/login', [
            'email' => 'naoexiste@exemplo.com',
            'password' => 'senhaerrada'
        ]);

        $response->assertStatus(401);
        $response->assertJsonFragment(['message' => 'Credenciais inválidas']);
    }

    /**
     * refresh token test
     */
    public function test_user_can_refresh_token()
    {
        $user = User::factory()->create();
        $refreshToken = $user->createToken('refresh_token', ['refresh'])->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $refreshToken)
            ->postJson('/api/refresh');

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'refresh_token']);
    }

    /**
     * refresh token unauthenticated user test
     */
    public function test_refresh_fails_without_authentication()
    {
        $response = $this->postJson('/api/refresh');

        $response->assertStatus(401);
    }

    /**
     * refresh token with a token without ability test
     */
    public function test_refresh_fails_without_refresh_ability()
    {
        $user = User::factory()->create();
        $token = $user->createToken('token-sem-refresh', ['access-api'])->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/refresh');

        $response->assertStatus(403);
        $response->assertJsonFragment(['message' => 'Token não autorizado para refresh']);
    }

    /**
     * reset password
     */
    public function test_user_can_reset_password()
    {
        Notification::fake();

        $user = User::factory()->create();
        $token = Password::createToken($user);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'novaSenha123!',
            'password_confirmation' => 'novaSenha123!',
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Senha redefinida com sucesso.']);

        $this->assertTrue(Hash::check('novaSenha123!', $user->fresh()->password));
    }

    /**
     * reset password with invalid token test
     */
    public function test_reset_password_fails_with_invalid_token()
    {
        $response = $this->postJson('/api/reset-password', [
            'email' => 'user@teste.com',
            'token' => 'invalido',
            'password' => 'novaSenha123'
        ]);

        $response->assertStatus(422); // ou 400 dependendo da implementação
    }

    /**
     * forgot password --> success
     */
    public function test_user_receives_password_reset_email()
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Link de redefinição enviado para o email.']);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    /**
     * forgot password --> invalid email
     */
    public function test_invalid_email_returns_error()
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'email-inexistente@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * logout test
     */
    public function test_user_can_logout()
    {
        $user = User::factory()->create();
        $token = $user->createToken('access_token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado com sucesso']);
    }

    /**
     * logout without authentication test
     */
    public function test_logout_fails_without_authentication()
    {
        $response = $this->postJson('/api/logout');

        $response->assertStatus(401);
    }

    /**
     * logout all test
     */
    public function test_user_can_logout_from_all_devices()
    {
        $user = User::factory()->create();

        $token1 = $user->createToken('device-1')->plainTextToken;
        $token2 = $user->createToken('device-2')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer ' . $token1)
            ->postJson('/api/logout-all')
            ->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado em todos os dispositivos.']);

        $this->assertCount(0, $user->tokens);
    }

    /**
     * logout all devices without authentication test
     */
    public function test_logout_all_fails_without_authentication()
    {
        $response = $this->postJson('/api/logout-all');

        $response->assertStatus(401);
    }

    /**
     * Returns 401 status code because there is no authenticated user
     */
    public function test_unauthenticated_user_receives_401()
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.']);
    }

    /**
     * clear expired tokens test
     */
    public function test_clear_resets_command_removes_expired_tokens()
    {
        // Simula tokens expirados
        DB::table('password_reset_tokens')->insert([
            'email' => 'user@example.com',
            'token' => 'fake-token',
            'created_at' => now()->subHours(2),
        ]);

        // Executa o comando
        $this->artisan('auth:clear-resets')
            ->assertExitCode(0);

        // Verifica se o token foi removido
        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'user@example.com',
        ]);
    }

    /**
     * email verification test
     */
    public function test_unverified_user_cannot_access_verified_route()
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);

        $this->actingAs($user)
            ->getJson('/api/user')
            ->assertStatus(403);
    }

    /**
     * verification email with invalid parameters test
     */
    public function test_verify_email_fails_with_invalid_signature()
    {
        $user = User::factory()->create();

        $url = "/api/email/verify/{$user->id}/hash-invalido";

        $token = $user->createToken('verificador')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson($url);

        $response->assertStatus(403); // ou 401 dependendo da implementação
    }

    /**
     * verification email requests limit exceeded test
     */
    public function test_verification_notification_throttling()
    {
        $user = User::factory()->create();

        $token = $user->createToken('verificador')->plainTextToken;

        for ($i = 0; $i < 10; $i++) {
            $response = $this->withHeader('Authorization', "Bearer {$token}")
                ->postJson('/api/email/verification-notification');

            if ($i >= 6) {
                $response->assertStatus(429);
            }
        }
    }
}
