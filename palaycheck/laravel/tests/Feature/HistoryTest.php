<?php

namespace Tests\Feature;

use App\Models\Detection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username, string $role = 'farmer'): User
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

    private function detection(User $user): Detection
    {
        return Detection::create([
            'user_id' => $user->id,
            'label' => 'Brown Planthopper',
            'confidence' => 0.87,
            'severity' => 'medium',
            'count' => 1,
            'source' => 'upload',
            'created_at' => now(),
        ]);
    }

    public function test_history_requires_authentication(): void
    {
        $this->get('/history')->assertRedirect('/login');
    }

    public function test_users_only_see_their_own_scans(): void
    {
        $owner = $this->user('owner');
        $other = $this->user('other');
        $detection = $this->detection($owner);

        $this->actingAs($owner)->get('/history')
            ->assertOk()
            ->assertSee('Brown Planthopper')
            ->assertSee(route('history.show', $detection));

        $this->actingAs($other)->get(route('history.show', $detection))->assertForbidden();
        $this->actingAs($other)->get('/history')->assertOk()->assertDontSee(route('history.show', $detection));
    }

    public function test_admins_can_view_and_delete_any_scan(): void
    {
        $owner = $this->user('owner');
        $admin = $this->user('boss', 'admin');
        $detection = $this->detection($owner);

        $this->actingAs($admin)->get(route('history.show', $detection))->assertOk();

        $this->actingAs($admin)->post(route('history.destroy', $detection))
            ->assertRedirect(route('history.index'));

        $this->assertDatabaseMissing('detections', ['id' => $detection->id]);
    }

    public function test_owners_can_delete_their_scan(): void
    {
        $owner = $this->user('owner');
        $detection = $this->detection($owner);

        $this->actingAs($owner)->post(route('history.destroy', $detection))
            ->assertRedirect(route('history.index'));

        $this->assertDatabaseMissing('detections', ['id' => $detection->id]);
    }
}
