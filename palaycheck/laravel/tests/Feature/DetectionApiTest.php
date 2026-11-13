<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DetectionApiTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::create([
            'full_name' => 'Farmer',
            'username' => 'farmer',
            'email' => 'farmer@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => 'farmer',
            'status' => 'active',
            'created_at' => now(),
        ]);
    }

    public function test_api_requires_authentication(): void
    {
        $this->postJson('/api/detect', [])->assertUnauthorized();
    }

    public function test_api_requires_an_image(): void
    {
        $this->actingAs($this->user())
            ->postJson('/api/detect', [])
            ->assertStatus(400)
            ->assertJson(['ok' => false]);
    }

    public function test_api_rejects_a_non_image_upload(): void
    {
        $this->actingAs($this->user())
            ->post('/api/detect', ['image' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain')])
            ->assertStatus(400)
            ->assertJson(['ok' => false]);
    }
}
