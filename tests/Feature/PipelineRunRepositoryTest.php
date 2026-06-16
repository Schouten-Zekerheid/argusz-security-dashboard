<?php

namespace Tests\Feature;

use App\Models\PipelineRun;
use App\Repositories\PipelineRunRepository;
use Carbon\Carbon;
use Tests\TestCase;

class PipelineRunRepositoryTest extends TestCase
{
    private PipelineRunRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        // Truncate pipeline runs before each test to ensure isolation in MongoDB
        PipelineRun::truncate();

        $this->repository = new PipelineRunRepository;
    }

    protected function tearDown(): void
    {
        PipelineRun::truncate();

        parent::tearDown();
    }

    public function test_get_latest_github_run_returns_null_when_none_exist(): void
    {
        $this->assertNull($this->repository->getLatestGithubRun());
    }

    public function test_get_latest_container_run_returns_null_when_none_exist(): void
    {
        $this->assertNull($this->repository->getLatestContainerRun());
    }

    public function test_get_latest_github_run_returns_correct_run(): void
    {
        // Create an older github run
        PipelineRun::create([
            'service_id' => '123',
            'ingestion_hash' => 'hash1',
            'schema_version' => 1,
            'meta' => ['tier' => 'github'],
            'runs' => [],
            'ingested_at' => Carbon::now()->subDays(2)->toDateTimeString(),
        ]);

        // Create a newer github run
        $latest = PipelineRun::create([
            'service_id' => '123',
            'ingestion_hash' => 'hash2',
            'schema_version' => 1,
            'meta' => ['tier' => 'github'],
            'runs' => [],
            'ingested_at' => Carbon::now()->subDay()->toDateTimeString(),
        ]);

        // Create a container run (should be ignored by getLatestGithubRun)
        PipelineRun::create([
            'service_id' => '123',
            'ingestion_hash' => 'hash3',
            'schema_version' => 1,
            'meta' => ['tier' => 'container'],
            'runs' => [],
            'ingested_at' => Carbon::now()->toDateTimeString(),
        ]);

        $result = $this->repository->getLatestGithubRun();

        $this->assertNotNull($result);
        $this->assertEquals($latest->id, $result->id);
    }

    public function test_get_latest_container_run_returns_correct_run(): void
    {
        // Create an older container run
        PipelineRun::create([
            'service_id' => '123',
            'ingestion_hash' => 'hash1',
            'schema_version' => 1,
            'meta' => ['tier' => 'container'],
            'runs' => [],
            'ingested_at' => Carbon::now()->subDays(2)->toDateTimeString(),
        ]);

        // Create a newer container run
        $latest = PipelineRun::create([
            'service_id' => '123',
            'ingestion_hash' => 'hash2',
            'schema_version' => 1,
            'meta' => ['tier' => 'container'],
            'runs' => [],
            'ingested_at' => Carbon::now()->subDay()->toDateTimeString(),
        ]);

        // Create a github run (should be ignored by getLatestContainerRun)
        PipelineRun::create([
            'service_id' => '123',
            'ingestion_hash' => 'hash3',
            'schema_version' => 1,
            'meta' => ['tier' => 'github'],
            'runs' => [],
            'ingested_at' => Carbon::now()->toDateTimeString(),
        ]);

        $result = $this->repository->getLatestContainerRun();

        $this->assertNotNull($result);
        $this->assertEquals($latest->id, $result->id);
    }
}
