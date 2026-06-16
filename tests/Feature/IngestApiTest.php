<?php

namespace Tests\Feature;

use App\Models\PipelineRun;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Services\IngestService;
use App\Services\OidcService;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Tests\TestCase;

class IngestApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Bypass OIDC JWT validation — no real GitHub token needed in tests
        $this->mock(OidcService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('validate')->andReturn(null);
        });
    }

    public function test_returns_401_without_bearer_token(): void
    {
        $response = $this->postJson('/api/ingest', $this->validPayload());

        $response->assertUnauthorized();
    }

    public function test_returns_422_when_meta_service_is_missing(): void
    {
        $payload = $this->validPayload();
        unset($payload['meta']['service']);

        $response = $this->withToken('fake')->postJson('/api/ingest', $payload);

        $response->assertUnprocessable();
    }

    public function test_accepts_custom_tool_keys(): void
    {
        $service = Mockery::mock(Service::class)->makePartial();

        $this->mock(
            ServiceRepository::class,
            function (MockInterface $mock) use ($service): void {
                $mock->shouldReceive('findByRepositoryUrl')->andReturn($service);
            }
        );

        $pipelineRun = Mockery::mock(PipelineRun::class)->makePartial();
        $pipelineRun->_id = 'run-id-abc123';

        $this->mock(
            IngestService::class,
            function (MockInterface $mock) use ($pipelineRun): void {
                $mock->shouldReceive('store')->andReturn($pipelineRun);
            }
        );

        $payload = $this->validPayload();
        $payload['runs'][0]['tool']['key'] = 'bandit';

        $response = $this->withToken('fake')->postJson('/api/ingest', $payload);

        $response->assertCreated();
    }

    public function test_returns_422_when_severity_is_invalid(): void
    {
        $payload = $this->validPayload();
        $payload['runs'][0]['findings'][] = [
            'type' => 'vulnerability',
            'severity' => 'EXTREME', // not in: CRITICAL, HIGH, MEDIUM, LOW, INFO
            'tool_severity' => 'HIGH',
            'reference_id' => 'CVE-2024-1234',
            'title' => 'Test vuln',
            'description' => null,
            'fingerprint' => 'sha256:abc',
            'first_seen_at' => now()->toIso8601String(),
            'details' => null,
        ];

        $response = $this->withToken('fake')->postJson('/api/ingest', $payload);

        $response->assertUnprocessable();
    }

    public function test_returns_422_when_service_not_found(): void
    {
        $this->mock(ServiceRepository::class, function (MockInterface $mock): void {
            $mock->shouldReceive('findByRepositoryUrl')->andReturn(null);
        });

        $response = $this->withToken('fake')
            ->postJson('/api/ingest', $this->validPayload());

        $response->assertUnprocessable();
    }

    public function test_returns_409_when_payload_is_duplicate(): void
    {
        $service = Mockery::mock(Service::class)->makePartial();

        $this->mock(
            ServiceRepository::class,
            function (MockInterface $mock) use ($service): void {
                $mock->shouldReceive('findByRepositoryUrl')->andReturn($service);
            }
        );

        $this->mock(IngestService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('store')
                ->andThrow(new ConflictHttpException('run already ingested'));
        });

        $response = $this->withToken('fake')
            ->postJson('/api/ingest', $this->validPayload());

        $response->assertConflict();
    }

    public function test_returns_201_with_pipeline_run_id_on_valid_payload(): void
    {
        $service = Mockery::mock(Service::class)->makePartial();

        $this->mock(
            ServiceRepository::class,
            function (MockInterface $mock) use ($service): void {
                $mock->shouldReceive('findByRepositoryUrl')->andReturn($service);
            }
        );

        $pipelineRun = Mockery::mock(PipelineRun::class)->makePartial();
        $pipelineRun->_id = 'run-id-abc123';

        $this->mock(
            IngestService::class,
            function (MockInterface $mock) use ($pipelineRun): void {
                $mock->shouldReceive('store')->andReturn($pipelineRun);
            }
        );

        $response = $this->withToken('fake')
            ->postJson('/api/ingest', $this->validPayload());

        $response->assertCreated();
        $response->assertJsonStructure(['pipeline_run_id']);
    }

    public function test_returns_201_with_pipeline_run_id_on_valid_container_payload(): void
    {
        $service = Mockery::mock(Service::class)->makePartial();

        $this->mock(
            ServiceRepository::class,
            function (MockInterface $mock) use ($service): void {
                $mock->shouldReceive('findByImageRef')
                    ->with('acr.example.com/my-service:latest')
                    ->andReturn($service);
                $mock->shouldReceive('findByRepositoryUrl')->andReturn($service);
            }
        );

        $pipelineRun = Mockery::mock(PipelineRun::class)->makePartial();
        $pipelineRun->_id = 'run-id-container123';

        $this->mock(
            IngestService::class,
            function (MockInterface $mock) use ($pipelineRun): void {
                $mock->shouldReceive('store')->andReturn($pipelineRun);
            }
        );

        $payload = $this->validPayload();
        $payload['meta']['tier'] = 'container';
        $payload['meta']['image_ref'] = 'acr.example.com/my-service:latest';
        $payload['runs'][0]['scan']['type'] = 'container_image';

        $response = $this->withToken('fake')->postJson('/api/ingest', $payload);

        $response->assertCreated();
        $response->assertJsonStructure(['pipeline_run_id']);
    }

    public function test_finds_service_by_image_ref_when_repository_url_mismatches(): void
    {
        $service = Mockery::mock(Service::class)->makePartial();

        $this->mock(
            ServiceRepository::class,
            function (MockInterface $mock) use ($service): void {
                $mock->shouldReceive('findByImageRef')
                    ->with('acr.example.com/my-service:latest')
                    ->andReturn($service);
                $mock->shouldReceive('findByRepositoryUrl')
                    ->never();
            }
        );

        $pipelineRun = Mockery::mock(PipelineRun::class)->makePartial();
        $pipelineRun->_id = 'run-id-image-ref-lookup';

        $this->mock(
            IngestService::class,
            function (MockInterface $mock) use ($pipelineRun): void {
                $mock->shouldReceive('store')->andReturn($pipelineRun);
            }
        );

        $payload = $this->validPayload();
        $payload['meta']['tier'] = 'container';
        $payload['meta']['image_ref'] = 'acr.example.com/my-service:latest';
        $payload['meta']['repository_url'] = 'https://github.com/different-org/different-repo';
        $payload['runs'][0]['scan']['type'] = 'container_image';

        $response = $this->withToken('fake')->postJson('/api/ingest', $payload);

        $response->assertCreated();
        $response->assertJsonStructure(['pipeline_run_id']);
    }

    private function validPayload(): array
    {
        return [
            'schema_version' => 1,
            'meta' => [
                'service' => 'my-service',
                'repository_url' => 'https://github.com/org/repo',
                'branch' => 'main',
                'environment' => 'production',
                'repository' => 'org/repo',
                'commit_hash' => 'abc123def456',
                'actor' => 'github-actions',
                'timestamp' => now()->toIso8601String(),
                'tier' => '1',
            ],
            'runs' => [
                [
                    'tool' => ['key' => 'trivy', 'category' => 'SCA'],
                    'scan' => [
                        'type' => 'filesystem',
                        'status' => 'success',
                        'artifact_ref' => 'trivy-results.json',
                    ],
                    'findings' => [],
                ],
            ],
        ];
    }
}
