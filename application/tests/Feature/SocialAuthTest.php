<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\User as ProviderUser;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * redirect to google test
     */
    public function test_can_redirect_to_google_provider()
    {
        $response = $this->getJson('/api/auth/redirect/google');

        $response->assertRedirect();
        $this->assertStringContainsString('accounts.google.com', $response->headers->get('Location'));
    }

    /**
     * google callback test
     */
    public function test_google_callback_creates_and_authenticates_user()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'test@example.com',
            'getName' => 'Test User',
            'getId' => 'google-123'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /**
     * google callback failure test
     */
    public function test_callback_google_returns_error_on_failure()
    {
        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Erro ao obter dados do Google'));

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertStatus(500);
        $response->assertJsonFragment(['message' => 'Erro ao obter dados do Google']);
    }

    /**
     * google callback missing email test
     */
    public function test_google_callback_returns_error_on_missing_email()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => null,
            'getName' => 'Usuário Sem Email',
            'getId' => 'google-456'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Email ausente ou inválido']);
    }

    /**
     * google callback missing name test
     */
    public function test_google_callback_returns_error_on_missing_name()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'teste@example.com',
            'getName' => null, // Nome ausente
            'getId' => 'google-123'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Nome ausente ou inválido']);
    }

    /**
     * google callback missing google id test
     */
    public function test_google_callback_returns_error_on_missing_google_id()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'teste@example.com',
            'getName' => 'Nome Teste',
            'getId' => null // ID ausente
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'ID do Google ausente ou inválido']);
    }

    /**
     * google callback duplicated email test
     */
    public function test_google_callback_handles_duplicate_email_gracefully()
    {
        User::factory()->create([
            'email' => 'duplicate@example.com'
        ]);

        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'duplicate@example.com',
            'getName' => 'Usuário Duplicado',
            'getId' => 'google-789'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    /**
     * google callback mismatch driver test
     */
    public function test_google_callback_returns_error_on_invalid_provider()
    {
        Socialite::shouldReceive('driver')
            ->with('google')
            ->andThrow(new \InvalidArgumentException('Provedor inválido'));

        $response = $this->getJson('/api/auth/callback/google');

        $response->assertStatus(500);
        $response->assertJsonFragment(['message' => 'Provedor inválido']);
    }

    /**
     * redirect to facebook test
     */
    public function test_can_redirect_to_facebook_provider()
    {
        $response = $this->getJson('/api/auth/redirect/facebook');

        $response->assertRedirect();
        $this->assertStringContainsString('facebook.com', $response->headers->get('Location'));
    }

    /**
     * facebook callback test
     */
    public function test_facebook_callback_creates_and_authenticates_user()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'test@example.com',
            'getName' => 'Test User',
            'getId' => 'facebook-123'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);

        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
    }

    /**
     * facebook callback failure test
     */
    public function test_callback_facebook_returns_error_on_failure()
    {
        Socialite::shouldReceive('driver->stateless->user')
            ->andThrow(new \Exception('Erro ao obter dados do Facebook'));

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertStatus(500);
        $response->assertJsonFragment(['message' => 'Erro ao obter dados do Facebook']);
    }

    /**
     * facebook callback missing email test
     */
    public function test_facebook_callback_returns_error_on_missing_email()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => null,
            'getName' => 'Usuário Sem Email',
            'getId' => 'facebook-456'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Email ausente ou inválido']);
    }

    /**
     * facebook callback missing name test
     */
    public function test_facebook_callback_returns_error_on_missing_name()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'teste@example.com',
            'getName' => null, // Nome ausente
            'getId' => 'facebook-123'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'Nome ausente ou inválido']);
    }

    /**
     * facebook callback missing facebook id test
     */
    public function test_facebook_callback_returns_error_on_missing_facebook_id()
    {
        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'teste@example.com',
            'getName' => 'Nome Teste',
            'getId' => null // ID ausente
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertStatus(422);
        $response->assertJsonFragment(['message' => 'ID do Facebook ausente ou inválido']);
    }

    /**
     * facebook callback duplicated email test
     */
    public function test_facebook_callback_handles_duplicate_email_gracefully()
    {
        User::factory()->create([
            'email' => 'duplicate@example.com'
        ]);

        $providerUser = \Mockery::mock(ProviderUser::class);
        $providerUser->shouldReceive([
            'getEmail' => 'duplicate@example.com',
            'getName' => 'Usuário Duplicado',
            'getId' => 'facebook-789'
        ]);

        Socialite::shouldReceive('driver->stateless->user')->andReturn($providerUser);

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertOk();
        $response->assertJsonStructure(['token', 'user' => ['id', 'name', 'email']]);
    }

    /**
     * facebook callback mismatch driver test
     */
    public function test_facebook_callback_returns_error_on_invalid_provider()
    {
        Socialite::shouldReceive('driver')
            ->with('facebook')
            ->andThrow(new \InvalidArgumentException('Provedor inválido'));

        $response = $this->getJson('/api/auth/callback/facebook');

        $response->assertStatus(500);
        $response->assertJsonFragment(['message' => 'Provedor inválido']);
    }
}
