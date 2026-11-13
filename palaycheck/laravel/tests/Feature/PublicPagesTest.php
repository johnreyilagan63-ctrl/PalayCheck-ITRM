<?php

namespace Tests\Feature;

use App\Models\LearningMaterial;
use App\Models\Pest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_home_page_lists_popular_pests(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Smarter Rice Farming Starts with Early Pest Detection')
            ->assertSee('Popular Rice Pests');
    }

    public function test_pests_can_be_searched_and_details_require_login(): void
    {
        $pest = Pest::where('status', 'active')->orderBy('name')->firstOrFail();

        $this->get('/pests')
            ->assertOk()
            ->assertSee($pest->name);

        $this->get(route('pests.show', $pest))->assertRedirect('/login');

        $this->get('/pests?q='.urlencode($pest->name))
            ->assertOk()
            ->assertSee($pest->name);
    }

    public function test_logged_in_farmer_can_view_pest_details_and_is_returned_after_login(): void
    {
        $user = User::create([
            'full_name' => 'Farmer One',
            'username' => 'farmer1',
            'email' => 'farmer1@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => 'farmer',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $pest = Pest::where('status', 'active')->orderBy('name')->firstOrFail();

        $this->get(route('pests.show', $pest))->assertRedirect('/login');

        $this->post('/login', [
            'identity' => 'farmer1',
            'password' => 'secret1',
        ])->assertRedirect(route('pests.show', $pest));

        $this->get(route('pests.show', $pest))
            ->assertOk()
            ->assertSee($pest->name)
            ->assertSee($pest->scientific_name);
    }

    public function test_learning_guides_render(): void
    {
        $item = LearningMaterial::where('status', 'published')->firstOrFail();

        $this->get('/learning')->assertOk()->assertSee($item->title);
        $this->get(route('learning.show', $item))->assertOk()->assertSee($item->title);
    }

    public function test_authenticated_farmer_pages_render(): void
    {
        $user = User::create([
            'full_name' => 'Farmer One',
            'username' => 'farmer1',
            'email' => 'farmer1@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => 'farmer',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $this->actingAs($user)->get('/dashboard')->assertOk()->assertSee('Good day, Farmer One!');
        $this->actingAs($user)->get('/detection')->assertOk()->assertSee('Scan Rice Pest');
        $this->actingAs($user)->get('/history')->assertOk()->assertSee('Detection History');
        $this->actingAs($user)->get('/advice')->assertOk()->assertSee('Smart Farming Advice');
    }

    public function test_advice_page_requires_login_and_filters_by_pest(): void
    {
        $user = User::create([
            'full_name' => 'Farmer One',
            'username' => 'farmer1',
            'email' => 'farmer1@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => 'farmer',
            'status' => 'active',
            'created_at' => now(),
        ]);

        $this->get('/advice')->assertRedirect('/login');

        $pest = Pest::firstOrFail();

        $this->actingAs($user)
            ->get('/advice?pest='.$pest->id)
            ->assertOk()
            ->assertSee('Smart Farming Advice');
    }
}
