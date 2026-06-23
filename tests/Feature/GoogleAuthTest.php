<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'services.google.workspace_domain' => 'azienda.it',
            'services.google.admin_email' => 'admin@azienda.it',
        ]);
    }

    public function test_workspace_user_is_created_and_logged_in(): void
    {
        $this->googleUser('1', 'Luca@azienda.it', 'Luca');

        $this->get('/login/google/callback')->assertRedirect(route('board'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'luca@azienda.it', 'google_id' => '1']);
    }

    public function test_preauthorized_external_user_can_log_in(): void
    {
        User::factory()->create(['email' => 'guest@example.com', 'is_enabled' => true]);
        $this->googleUser('2', 'guest@example.com', 'Guest');

        $this->get('/login/google/callback')->assertRedirect(route('board'));

        $this->assertAuthenticated();
    }

    public function test_unknown_external_user_is_rejected(): void
    {
        $this->googleUser('3', 'guest@example.com', 'Guest');

        $this->get('/login/google/callback')->assertForbidden();

        $this->assertGuest();
    }

    public function test_disabled_workspace_user_is_rejected(): void
    {
        User::factory()->create(['email' => 'luca@azienda.it', 'is_enabled' => false]);
        $this->googleUser('4', 'luca@azienda.it', 'Luca');

        $this->get('/login/google/callback')->assertForbidden();
    }

    public function test_invalid_google_state_restarts_login_without_500(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andThrow(new InvalidStateException);
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get('/login/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHas('login_error');

        $this->assertGuest();
    }
    public function test_google_avatar_url_column_accepts_google_long_urls(): void
    {
        $column = collect(Schema::getColumns('users'))->firstWhere('name', 'avatar_url');

        $this->assertSame('text', $column['type_name']);
    }
    public function test_workspace_login_accepts_long_google_avatar_urls(): void
    {
        $this->googleUser('5', 'avatar@azienda.it', 'Avatar', 'https://lh3.googleusercontent.com/'.str_repeat('x', 600));

        $this->get('/login/google/callback')->assertRedirect(route('board'));

        $this->assertDatabaseHas('users', ['email' => 'avatar@azienda.it']);
    }
    private function googleUser(string $id, string $email, string $name, string $avatar = 'https://example.com/avatar.jpg'): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn(new class($id, $email, $name, $avatar)
        {
            public function __construct(private string $id, private string $email, private string $name, private string $avatar) {}
            public function getId(): string { return $this->id; }
            public function getEmail(): string { return $this->email; }
            public function getName(): string { return $this->name; }
            public function getAvatar(): string { return $this->avatar; }
        });
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
