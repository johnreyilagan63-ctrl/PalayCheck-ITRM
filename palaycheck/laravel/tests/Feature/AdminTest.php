<?php

namespace Tests\Feature;

use App\Models\Pest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username, string $role): User
    {
        return User::create([
            'full_name' => ucfirst($username),
            'username' => $username,
            'email' => $username.'@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => $role,
            'status' => 'active',
            'created_at' => now(),
        ]);
    }

    public function test_guests_and_farmers_cannot_open_the_admin_panel(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');

        $this->actingAs($this->user('farmer', 'farmer'))
            ->get('/admin/dashboard')
            ->assertForbidden();
    }

    public function test_admin_can_open_dashboard_and_resource_pages(): void
    {
        $admin = $this->user('boss', 'admin');

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk()->assertSee('Monitor and manage');
        $this->actingAs($admin)->get('/admin/users')->assertOk()->assertSee('Users');
        $this->actingAs($admin)->get('/admin/pests')->assertOk()->assertSee('Pests');
        $this->actingAs($admin)->get('/admin/settings')->assertOk()->assertSee('Detection confidence threshold');

        foreach (['users', 'pests', 'advice', 'learning'] as $resource) {
            $this->actingAs($admin)->get(route('admin.resources.create', $resource))->assertOk()->assertSee('New');
        }
    }

    public function test_admin_can_create_update_and_delete_a_pest(): void
    {
        $admin = $this->user('boss', 'admin');

        $this->actingAs($admin)->post('/admin/pests', [
            'name' => 'Test Weevil',
            'scientific_name' => 'Testus weevil',
            'description' => 'A test pest.',
            'symptoms' => 'Holes in leaves.',
            'effects' => 'Reduced yield.',
            'status' => 'active',
        ])->assertRedirect(route('admin.resources.index', 'pests'));

        $pest = Pest::where('name', 'Test Weevil')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.resources.edit', ['pests', $pest->id]))
            ->assertOk()
            ->assertSee('Testus weevil');

        $this->actingAs($admin)->post(route('admin.resources.update', ['pests', $pest->id]), [
            'name' => 'Test Weevil Updated',
            'scientific_name' => 'Testus weevil',
            'description' => 'A test pest.',
            'symptoms' => 'Holes in leaves.',
            'effects' => 'Reduced yield.',
            'status' => 'inactive',
        ])->assertRedirect(route('admin.resources.index', 'pests'));

        $pest->refresh();
        $this->assertSame('Test Weevil Updated', $pest->name);
        $this->assertSame('inactive', $pest->status);

        $this->actingAs($admin)->post(route('admin.resources.destroy', ['pests', $pest->id]))
            ->assertRedirect(route('admin.resources.index', 'pests'));

        $this->assertDatabaseMissing('pests', ['id' => $pest->id]);
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = $this->user('boss', 'admin');

        $this->actingAs($admin)->post(route('admin.resources.destroy', ['users', $admin->id]))
            ->assertRedirect(route('admin.resources.index', 'users'));

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_change_the_detection_threshold(): void
    {
        $admin = $this->user('boss', 'admin');

        $this->actingAs($admin)->post('/admin/settings', ['threshold' => '75'])
            ->assertRedirect(route('admin.settings.index'));

        $this->assertDatabaseHas('settings', ['key' => 'threshold', 'value' => '0.75']);
    }
}
