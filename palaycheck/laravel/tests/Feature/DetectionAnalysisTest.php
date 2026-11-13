<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DetectionAnalysisTest extends TestCase
{
    use RefreshDatabase;

    private string $uploadDir = '';

    /** @var array<int, string> */
    private array $sourceFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed();
        $this->uploadDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'palaycheck-test-'.uniqid();
        config([
            'palaycheck.upload_dir' => $this->uploadDir,
            'palaycheck.provider' => 'local',
        ]);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->uploadDir.DIRECTORY_SEPARATOR.'*') ?: [] as $file) {
            @unlink($file);
        }

        foreach ($this->sourceFiles as $file) {
            @unlink($file);
        }

        @rmdir($this->uploadDir);

        parent::tearDown();
    }

    private function user(): User
    {
        return User::create([
            'full_name' => 'Analysis Tester',
            'username' => 'analysis_tester',
            'email' => 'analysis_tester@example.test',
            'password_hash' => Hash::make('secret1'),
            'role' => 'farmer',
            'status' => 'active',
            'created_at' => now(),
        ]);
    }

    private function image(): UploadedFile
    {
        $copy = sys_get_temp_dir().DIRECTORY_SEPARATOR.'palaycheck-src-'.uniqid().'.jpg';
        copy(public_path('static/img/pests/yellow-rice-stem-borer.jpg'), $copy);
        $this->sourceFiles[] = $copy;

        return new UploadedFile($copy, 'pest.jpg', 'image/jpeg', null, true);
    }

    public function test_uploaded_image_is_analyzed_matched_and_saved(): void
    {
        Http::fake([
            '*/detect' => Http::response([
                'detections' => [
                    ['label' => 'stem_borer', 'confidence' => 0.91, 'box' => [0.05, 0.05, 0.55, 0.55]],
                ],
            ]),
        ]);

        $user = $this->user();

        $response = $this->actingAs($user)->post('/api/detect', [
            'image' => $this->image(),
            'save' => '1',
            'source' => 'upload',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('saved', true)
            ->assertJsonPath('detections.0.pest_name', 'Yellow Rice Stem Borer')
            ->assertJsonPath('severity', 'medium');

        $this->assertDatabaseHas('detections', [
            'user_id' => $user->id,
            'label' => 'Yellow Rice Stem Borer',
            'severity' => 'medium',
        ]);

        $this->assertNotEmpty(glob($this->uploadDir.DIRECTORY_SEPARATOR.'*'));
    }

    public function test_matched_pest_is_preferred_over_an_unknown_label(): void
    {
        Http::fake([
            '*/detect' => Http::response([
                'detections' => [
                    ['label' => 'butterfly', 'confidence' => 0.95, 'box' => [0.1, 0.1, 0.3, 0.3]],
                    ['label' => 'planthopper', 'confidence' => 0.64, 'box' => [0.4, 0.4, 0.6, 0.6]],
                ],
            ]),
        ]);

        $response = $this->actingAs($this->user())->post('/api/detect', [
            'image' => $this->image(),
            'save' => '0',
            'source' => 'camera',
        ]);

        $response->assertOk()
            ->assertJsonPath('detections.0.label', 'planthopper')
            ->assertJsonPath('detections.0.pest_name', 'Brown Planthopper');
    }

    public function test_detection_explains_how_to_start_the_local_service(): void
    {
        config([
            'palaycheck.inference.url' => 'http://127.0.0.1:5001',
            'palaycheck.roboflow.api_key' => '',
        ]);

        Http::fake(function (): void {
            throw new ConnectionException('Connection refused');
        });

        $response = $this->actingAs($this->user())->post('/api/detect', [
            'image' => $this->image(),
            'save' => '0',
            'source' => 'camera',
        ]);

        $response->assertStatus(503)->assertJsonPath('ok', false);
        $this->assertStringContainsString('start_inference.bat', (string) $response->json('error'));
    }

    public function test_detection_reports_a_clear_message_when_local_and_roboflow_fail_together(): void
    {
        config([
            'palaycheck.inference.url' => 'http://127.0.0.1:5001',
            'palaycheck.roboflow.api_key' => 'demo-key',
            'palaycheck.roboflow.workflows' => ['https://example.com/workflow'],
        ]);

        Http::fake([
            'http://127.0.0.1:5001/*' => function (): void {
                throw new ConnectionException('Connection refused');
            },
            'https://example.com/workflow' => Http::response([
                'reason' => 'credit_cap_exceeded',
                'message' => 'No inference credits left.',
            ], 402),
        ]);

        $response = $this->actingAs($this->user())->post('/api/detect', [
            'image' => $this->image(),
            'save' => '0',
            'source' => 'camera',
        ]);

        $response->assertStatus(503)->assertJsonPath('ok', false);
        $message = (string) $response->json('error');
        $this->assertStringContainsString('start_inference.bat', $message);
        $this->assertStringContainsString('Roboflow has no inference credits left', $message);
        $this->assertStringNotContainsString('Fallback:', $message);
    }

    public function test_roboflow_primary_reports_the_credit_cap_without_a_local_fallback(): void
    {
        config([
            'palaycheck.provider' => 'roboflow',
            'palaycheck.roboflow.api_key' => 'demo-key',
            'palaycheck.roboflow.workflows' => ['https://example.com/workflow'],
        ]);

        Http::fake([
            'https://example.com/workflow' => Http::response([
                'reason' => 'credit_cap_exceeded',
                'message' => 'No inference credits left.',
            ], 402),
        ]);

        $response = $this->actingAs($this->user())->post('/api/detect', [
            'image' => $this->image(),
            'save' => '0',
            'source' => 'camera',
        ]);

        $response->assertStatus(503)->assertJsonPath('ok', false);
        $message = (string) $response->json('error');
        $this->assertStringContainsString('app.roboflow.com/settings/plan', $message);
        $this->assertStringNotContainsString('start_inference.bat', $message);

        Http::assertNotSent(static fn ($request): bool => str_contains($request->url(), '127.0.0.1:5001'));
    }

    public function test_detection_status_reports_roboflow_as_the_online_provider(): void
    {
        config([
            'palaycheck.provider' => 'roboflow',
            'palaycheck.roboflow.api_key' => 'demo-key',
            'palaycheck.roboflow.api_url' => 'https://api.roboflow.com',
        ]);

        Http::fake([
            'https://api.roboflow.com*' => Http::response(['workspace' => 'demo'], 200),
            'http://127.0.0.1:5001/*' => function (): void {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->actingAs($this->user())
            ->getJson('/api/detection-status')
            ->assertOk()
            ->assertJson([
                'ready' => true,
                'primary_provider' => 'roboflow',
                'roboflow_online' => true,
                'auto_started' => false,
            ]);
    }

    public function test_saved_roboflow_scan_uses_one_workflow_and_can_be_viewed(): void
    {
        $primaryWorkflow = 'https://example.com/primary';
        $secondaryWorkflow = 'https://example.com/secondary';
        config([
            'palaycheck.provider' => 'roboflow',
            'palaycheck.roboflow.api_key' => 'demo-key',
            'palaycheck.roboflow.workflows' => [$primaryWorkflow, $secondaryWorkflow],
        ]);

        Http::fake([
            'https://example.com/*' => Http::response([
                'outputs' => [[
                    'predictions' => [
                        'image' => ['width' => 640, 'height' => 480],
                        'predictions' => [[
                            'class' => 'stem_borer',
                            'confidence' => 0.9,
                            'x' => 0.5,
                            'y' => 0.5,
                            'width' => 0.2,
                            'height' => 0.2,
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $user = $this->user();
        $response = $this->actingAs($user)->post('/api/detect', [
            'image' => $this->image(),
            'save' => '1',
            'source' => 'upload',
        ]);

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('saved', true)
            ->assertJsonPath('detections.0.pest_name', 'Yellow Rice Stem Borer');

        Http::assertSentCount(1);
        Http::assertSent(static fn ($request): bool => $request->url() === $primaryWorkflow);
        Http::assertNotSent(static fn ($request): bool => $request->url() === $secondaryWorkflow);

        $this->actingAs($user)
            ->get(route('history.show', $response->json('id')))
            ->assertOk()
            ->assertSee('Yellow Rice Stem Borer');

        Http::assertNotSent(static fn ($request): bool => str_contains($request->url(), '127.0.0.1:5001'));
    }

    public function test_detection_status_reports_the_local_service_as_ready(): void
    {
        config(['palaycheck.inference.url' => 'http://127.0.0.1:5001']);

        Http::fake([
            '*/health' => Http::response(['ready' => true], 200),
        ]);

        $this->actingAs($this->user())
            ->getJson('/api/detection-status')
            ->assertOk()
            ->assertJson([
                'ready' => true,
                'local_configured' => true,
                'local_online' => true,
            ]);
    }

    public function test_detection_status_reports_an_offline_service(): void
    {
        config([
            'palaycheck.inference.url' => 'http://127.0.0.1:5001',
            'palaycheck.roboflow.api_key' => '',
        ]);

        Http::fake(function (): void {
            throw new ConnectionException('Connection refused');
        });

        $this->actingAs($this->user())
            ->getJson('/api/detection-status')
            ->assertOk()
            ->assertJson([
                'ready' => false,
                'local_online' => false,
                'roboflow_configured' => false,
            ]);
    }
}
