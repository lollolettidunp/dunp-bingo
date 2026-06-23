<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
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

    private function googleUser(string $id, string $email, string $name): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->andReturn(new class($id, $email, $name)
        {
            public function __construct(private string $id, private string $email, private string $name) {}
            public function getId(): string { return $this->id; }
            public function getEmail(): string { return $this->email; }
            public function getName(): string { return $this->name; }
            public function getAvatar(): string { return 'https://example.com/avatar.jpg'; }
        });
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }
}
