<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(string $username = 'farmer', string $role = 'farmer', string $status = 'active'): User
    {
        return User::create([
            'full_name' => ucfirst($username).' User',
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => $role,
            'status' => $status,
            'created_at' => now(),
        ]);
    }

    public function test_registration_creates_a_farmer_with_a_hashed_password(): void
    {
        $this->post('/register', [
            'full_name' => 'New Farmer',
            'username' => 'newfarmer',
            'email' => 'NEWFARMER@example.test',
            'password' => 'secret1',
            'confirm' => 'secret1',
        ])->assertRedirect('/login');

        $this->assertGuest();

        $user = User::where('username', 'newfarmer')->firstOrFail();
        $this->assertSame('newfarmer@example.test', $user->email);
        $this->assertSame('farmer', $user->role);
        $this->assertSame('active', $user->status);
        $this->assertTrue(Hash::check('secret1', $user->password_hash));
    }

    public function test_registration_rejects_duplicate_username_or_email(): void
    {
        $this->createUser('farmer');

        $this->from('/register')->post('/register', [
            'full_name' => 'Other Farmer',
            'username' => 'farmer',
            'email' => 'other@example.test',
            'password' => 'secret1',
            'confirm' => 'secret1',
        ])->assertRedirect('/register');

        $this->assertGuest();
        $this->assertSame(1, User::where('username', 'farmer')->count());
    }

    public function test_login_redirects_farmers_to_dashboard_and_logs_activity(): void
    {
        $user = $this->createUser('farmer');

        $this->post('/login', [
            'identity' => 'farmer',
            'password' => 'secret1',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'login']);
    }

    public function test_admin_login_redirects_to_admin_dashboard(): void
    {
        $this->createUser('boss', 'admin');

        $this->post('/login', [
            'identity' => 'boss',
            'password' => 'secret1',
        ])->assertRedirect('/admin/dashboard');
    }

    public function test_inactive_users_cannot_log_in(): void
    {
        $this->createUser('sleeper', 'farmer', 'inactive');

        $this->post('/login', [
            'identity' => 'sleeper',
            'password' => 'secret1',
        ])->assertRedirect();

        $this->assertGuest();
    }

    public function test_logout_clears_the_session(): void
    {
        $user = $this->createUser('farmer');

        $this->actingAs($user)->get('/logout')->assertRedirect('/');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['user_id' => $user->id, 'action' => 'logout']);
    }

    public function test_remember_me_creates_a_remember_token(): void
    {
        $user = $this->createUser('farmer');

        $this->post('/login', [
            'identity' => 'farmer',
            'password' => 'secret1',
            'remember' => '1',
        ]);

        $this->assertNotNull($user->fresh()->remember_token);
    }
}
