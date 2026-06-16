<?php

namespace Tests\Unit;

use App\Models\PipelineRun;
use App\Models\Service;
use App\Repositories\FindingStatusRepository;
use App\Repositories\PipelineRunRepository;
use App\Services\IngestService;
use App\Services\JiraService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class IngestServiceTest extends TestCase
{
    private FindingStatusRepository|MockInterface $findingStatusRepo;

    private PipelineRunRepository|MockInterface $pipelineRunRepo;

    private JiraService|MockInterface $jiraService;

    private NotificationService|MockInterface $notificationService;

    private IngestService $ingestService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->findingStatusRepo = Mockery::mock(FindingStatusRepository::class);
        $this->pipelineRunRepo = Mockery::mock(PipelineRunRepository::class);
        $this->jiraService = Mockery::mock(JiraService::class);
        $this->jiraService->shouldReceive('statusSyncEnabled')->andReturn(false)->byDefault();
        $this->notificationService = Mockery::mock(NotificationService::class);
        $this->notificationService->shouldReceive('criticalFindingsEnabled')->andReturn(false)->byDefault();

        $this->ingestService = new IngestService(
            $this->pipelineRunRepo,
            $this->findingStatusRepo,
            $this->jiraService,
            $this->notificationService,
        );
    }

    public function test_branch_scan_calls_upsert_but_not_mark_resolved(): void
    {
        $service = $this->makeService(defaultBranch: 'master');
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'feature/my-branch');

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->andReturn($pipelineRun);

        $this->findingStatusRepo->shouldReceive('upsert')
            ->once()
            ->with($service, $pipelineRun, 'github', Mockery::type('array'), Mockery::type('array'));
        $this->findingStatusRepo->shouldNotReceive('markReturning');
        $this->findingStatusRepo->shouldNotReceive('markResolved');

        $this->ingestService->store($service, $payload);
    }

    public function test_default_branch_scan_calls_upsert_and_mark_resolved(): void
    {
        $service = $this->makeService(defaultBranch: 'master');
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'master');

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->andReturn($pipelineRun);

        $this->findingStatusRepo->shouldReceive('upsert')
            ->once()
            ->with($service, $pipelineRun, 'github', Mockery::type('array'), Mockery::type('array'));
        $this->findingStatusRepo->shouldReceive('markReturning')
            ->once()
            ->with('service-id-123', 'github', 'run-id-abc123', ['sha256:abc123'])
            ->andReturn([]);
        $this->findingStatusRepo->shouldReceive('markResolved')
            ->once()
            ->with('service-id-123', 'github', ['sha256:abc123'])
            ->andReturn([]);

        $this->ingestService->store($service, $payload);
    }

    public function test_branch_scan_without_findings_does_not_call_mark_resolved(): void
    {
        $service = $this->makeService(defaultBranch: 'main');
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'fix/something', findings: []);

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->andReturn($pipelineRun);

        $this->findingStatusRepo->shouldNotReceive('upsert');
        $this->findingStatusRepo->shouldNotReceive('markReturning');
        $this->findingStatusRepo->shouldNotReceive('markResolved');

        $this->ingestService->store($service, $payload);
    }

    public function test_default_branch_fetched_from_github_when_not_stored(): void
    {
        $service = $this->makeService(defaultBranch: null);
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'master');

        Http::fake([
            'api.github.com/repos/org/repo' => Http::response(['default_branch' => 'master'], 200),
        ]);

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->andReturn($pipelineRun);

        // master scan → should call mark methods
        $this->findingStatusRepo->shouldReceive('upsert')
            ->once()
            ->with($service, $pipelineRun, 'github', Mockery::type('array'), Mockery::type('array'));
        $this->findingStatusRepo->shouldReceive('markReturning')
            ->once()
            ->with('service-id-123', 'github', 'run-id-abc123', ['sha256:abc123'])
            ->andReturn([]);
        $this->findingStatusRepo->shouldReceive('markResolved')
            ->once()
            ->with('service-id-123', 'github', ['sha256:abc123'])
            ->andReturn([]);

        $this->ingestService->store($service, $payload);

        $this->assertEquals('master', $service->default_branch);
    }

    public function test_github_api_not_called_when_default_branch_already_stored(): void
    {
        $service = $this->makeService(defaultBranch: 'main');
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'feature/test');

        Http::fake(); // no requests should be made

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->andReturn($pipelineRun);

        $this->findingStatusRepo->shouldReceive('upsert')->once();
        $this->findingStatusRepo->shouldNotReceive('markReturning');
        $this->findingStatusRepo->shouldNotReceive('markResolved');

        $this->ingestService->store($service, $payload);

        Http::assertNothingSent();
    }

    public function test_pipeline_run_always_created_regardless_of_branch(): void
    {
        $service = $this->makeService(defaultBranch: 'master');
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'feature/some-branch');

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->once()->andReturn($pipelineRun);

        $this->findingStatusRepo->shouldReceive('upsert')->once();
        $this->findingStatusRepo->shouldNotReceive('markResolved');

        $result = $this->ingestService->store($service, $payload);

        $this->assertInstanceOf(PipelineRun::class, $result);
    }

    public function test_container_default_branch_scan_resolves_only_container_findings(): void
    {
        $service = $this->makeService(defaultBranch: 'master');
        $pipelineRun = $this->makePipelineRun();
        $payload = $this->makePayload(branch: 'master');
        $payload['meta']['tier'] = 'container';
        $payload['runs'][0]['scan']['type'] = 'container_image';

        $this->pipelineRunRepo->shouldReceive('existsByHash')->andReturn(false);
        $this->pipelineRunRepo->shouldReceive('create')->andReturn($pipelineRun);

        $this->findingStatusRepo->shouldReceive('upsert')
            ->once()
            ->with($service, $pipelineRun, 'container', Mockery::type('array'), Mockery::type('array'));
        $this->findingStatusRepo->shouldReceive('markReturning')
            ->once()
            ->with('service-id-123', 'container', 'run-id-abc123', ['sha256:abc123'])
            ->andReturn([]);
        $this->findingStatusRepo->shouldReceive('markResolved')
            ->once()
            ->with('service-id-123', 'container', ['sha256:abc123'])
            ->andReturn([]);

        $this->ingestService->store($service, $payload);
    }

    private function makeService(?string $defaultBranch): Service
    {
        $service = Mockery::mock(Service::class)->makePartial();
        $service->_id = 'service-id-123';
        $service->name = 'test-service';
        $service->repository_url = 'https://github.com/org/repo';
        $service->default_branch = $defaultBranch;
        $service->shouldReceive('save')->andReturnSelf();

        return $service;
    }

    private function makePipelineRun(): PipelineRun
    {
        $run = Mockery::mock(PipelineRun::class)->makePartial();
        $run->_id = 'run-id-abc123';

        return $run;
    }

    private function makePayload(string $branch, ?array $findings = null): array
    {
        $findings ??= [
            [
                'type' => 'vulnerability',
                'severity' => 'HIGH',
                'tool_severity' => 'HIGH',
                'reference_id' => 'CVE-2024-1234',
                'title' => 'Test vulnerability',
                'description' => null,
                'fingerprint' => 'sha256:abc123',
                'first_seen_at' => now()->toIso8601String(),
                'details' => null,
            ],
        ];

        return [
            'schema_version' => 1,
            'meta' => [
                'service' => 'test-service',
                'repository_url' => 'https://github.com/org/repo',
                'branch' => $branch,
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
                    'findings' => $findings,
                ],
            ],
        ];
    }
}
