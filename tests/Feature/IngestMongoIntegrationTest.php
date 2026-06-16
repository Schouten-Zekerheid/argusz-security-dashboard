<?php

namespace Tests\Feature;

use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use App\Services\IngestService;
use Illuminate\Support\Facades\DB;
use MongoDB\Laravel\Eloquent\Model as MongoModel;
use Tests\TestCase;
use Throwable;

/**
 * Real MongoDB persistence test for the ingest pipeline. Unlike IngestServiceTest
 * (which mocks the repositories), this drives IngestService against a live Mongo
 * instance and reads the documents back to prove they were written.
 *
 * Requires a reachable Mongo (see .env.testing -> DOCUMENTDB_DSN). The test skips
 * itself when no connection is available so it never breaks CI runs without Mongo.
 */
class IngestMongoIntegrationTest extends TestCase
{
    private ?Service $service = null;

    protected function setUp(): void
    {
        parent::setUp();

        $dsn = env('DOCUMENTDB_DSN');
        $database = env('DOCUMENTDB_DATABASE');

        if (! $dsn || ! $database) {
            $this->markTestSkipped('DOCUMENTDB_DSN/DOCUMENTDB_DATABASE not set; skipping Mongo integration test.');
        }

        config([
            'database.connections.mongodb.driver' => 'mongodb',
            'database.connections.mongodb.dsn' => $dsn,
            'database.connections.mongodb.database' => $database,
        ]);

        DB::purge('mongodb');

        try {
            DB::connection('mongodb')->getMongoClient()->listDatabases();
        } catch (Throwable $e) {
            $this->markTestSkipped('Mongo not reachable: '.$e->getMessage());
        }

        $this->clearCollections();
    }

    protected function tearDown(): void
    {
        if ($this->service !== null) {
            $this->clearCollections();
        }

        parent::tearDown();
    }

    public function test_ingest_persists_pipeline_run_and_finding_status_to_mongo(): void
    {
        $this->service = Service::create([
            'name' => 'integration-service',
            'repository_url' => 'https://github.com/org/repo',
            'active' => true,
            'default_branch' => 'main',
        ]);

        $payload = [
            'schema_version' => 1,
            'meta' => [
                'service' => 'integration-service',
                'repository_url' => 'https://github.com/org/repo',
                'branch' => 'main',
                'environment' => 'production',
                'repository' => 'org/repo',
                'commit_hash' => 'deadbeef',
                'actor' => 'integration-test',
                'timestamp' => now()->toIso8601String(),
                'tier' => '1',
            ],
            'runs' => [
                [
                    'tool' => ['key' => 'trivy', 'category' => 'SCA'],
                    'scan' => ['type' => 'filesystem', 'status' => 'success', 'artifact_ref' => 'trivy.json'],
                    'findings' => [
                        [
                            'type' => 'vulnerability',
                            'severity' => 'HIGH',
                            'tool_severity' => 'HIGH',
                            'reference_id' => 'CVE-2024-9999',
                            'title' => 'Integration test finding',
                            'description' => null,
                            'fingerprint' => 'sha256:integration-test',
                            'first_seen_at' => now()->toIso8601String(),
                            'details' => null,
                        ],
                    ],
                ],
            ],
        ];

        $ingestService = app(IngestService::class);
        $pipelineRun = $ingestService->store($this->service, $payload);

        // Read the pipeline run back from Mongo by its id.
        $storedRun = PipelineRun::find((string) $pipelineRun->_id);
        $this->assertNotNull($storedRun, 'PipelineRun was not persisted to Mongo.');
        $this->assertSame((string) $this->service->_id, $storedRun->service_id);
        $this->assertSame('deadbeef', $storedRun->meta['commit_hash']);

        // Read the finding status back from Mongo by fingerprint.
        $storedFinding = FindingStatus::where('service_id', (string) $this->service->_id)
            ->where('fingerprint', 'sha256:integration-test')
            ->first();
        $this->assertNotNull($storedFinding, 'FindingStatus was not persisted to Mongo.');
        $this->assertSame('open', $storedFinding->current_status);
        $this->assertSame('HIGH', $storedFinding->severity);
        $this->assertSame('github', $storedFinding->scan_source);
    }

    private function clearCollections(): void
    {
        foreach ([Service::class, PipelineRun::class, FindingStatus::class] as $model) {
            /** @var MongoModel $instance */
            $instance = new $model;
            DB::connection('mongodb')
                ->getCollection($instance->getTable())
                ->deleteMany([]);
        }
    }
}
