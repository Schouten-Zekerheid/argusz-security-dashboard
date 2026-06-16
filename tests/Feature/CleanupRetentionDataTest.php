<?php

namespace Tests\Feature;

use App\Models\PipelineRun;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CleanupRetentionDataTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Truncate pipeline runs before each test to ensure isolation in MongoDB
        PipelineRun::truncate();
    }

    protected function tearDown(): void
    {
        PipelineRun::truncate();

        parent::tearDown();
    }

    public function test_old_pipeline_runs_are_partially_cleaned(): void
    {
        // 1. Arrange: Create an old pipeline run (35 days old)
        $oldDate = Carbon::now()->subDays(35)->toDateTimeString();
        $run = PipelineRun::create([
            'service_id' => 'service-123',
            'ingestion_hash' => 'hash-old',
            'schema_version' => 1,
            'meta' => [
                'branch' => 'main',
                'commit_hash' => 'commit-old',
            ],
            'ingested_at' => $oldDate,
            'runs' => [
                [
                    'tool' => ['key' => 'trivy', 'category' => 'SCA'],
                    'findings' => [
                        [
                            'fingerprint' => 'fp-1',
                            'severity' => 'HIGH',
                            'title' => 'SQL Injection Vulnerability',
                            'description' => 'A SQL injection vulnerability exists in...',
                            'details' => [
                                'file_path' => 'app/Http/Controllers/UserController.php',
                                'line_start' => 45,
                                'raw_output' => 'stacktrace or other heavy details...',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // Verify pre-conditions
        $this->assertNull($run->is_cleaned);
        $this->assertEquals('A SQL injection vulnerability exists in...', $run->runs[0]['findings'][0]['description']);
        $this->assertEquals(45, $run->runs[0]['findings'][0]['details']['line_start']);

        // 2. Act: Run the Artisan command
        $exitCode = Artisan::call('app:cleanup-retention-data');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Starting Data Retention Cleanup', $output);
        $this->assertStringContainsString('Data Retention Cleanup Succesvol Voltooid!', $output);
        $this->assertStringContainsString('Totaal opgeschoonde runs: 1', $output);

        // 3. Assert: Verify the old run was partially cleaned
        $cleanedRun = PipelineRun::find($run->_id);
        $this->assertTrue($cleanedRun->is_cleaned);

        $findings = $cleanedRun->runs[0]['findings'];
        $this->assertCount(1, $findings);

        // description and details should be nullified
        $this->assertNull($findings[0]['description']);
        $this->assertNull($findings[0]['details']);

        // crucial metadata is preserved intact for trends/scores
        $this->assertEquals('fp-1', $findings[0]['fingerprint']);
        $this->assertEquals('HIGH', $findings[0]['severity']);
        $this->assertEquals('SQL Injection Vulnerability', $findings[0]['title']);
    }

    public function test_young_pipeline_runs_are_not_cleaned(): void
    {
        // 1. Arrange: Create a young pipeline run (15 days old)
        $youngDate = Carbon::now()->subDays(15)->toDateTimeString();
        $run = PipelineRun::create([
            'service_id' => 'service-123',
            'ingestion_hash' => 'hash-young',
            'schema_version' => 1,
            'meta' => [
                'branch' => 'main',
                'commit_hash' => 'commit-young',
            ],
            'ingested_at' => $youngDate,
            'runs' => [
                [
                    'tool' => ['key' => 'trivy', 'category' => 'SCA'],
                    'findings' => [
                        [
                            'fingerprint' => 'fp-2',
                            'severity' => 'LOW',
                            'title' => 'Deprecated library used',
                            'description' => 'The library php-jwt is deprecated...',
                            'details' => [
                                'file_path' => 'composer.json',
                                'line_start' => 12,
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        // 2. Act: Run the Artisan command
        $exitCode = Artisan::call('app:cleanup-retention-data');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Totaal opgeschoonde runs: 0', $output);

        // 3. Assert: Verify the young run was NOT modified
        $freshRun = PipelineRun::find($run->_id);
        $this->assertNull($freshRun->is_cleaned);

        $findings = $freshRun->runs[0]['findings'];
        $this->assertEquals('The library php-jwt is deprecated...', $findings[0]['description']);
        $this->assertEquals('composer.json', $findings[0]['details']['file_path']);
    }

    public function test_already_cleaned_runs_are_skipped(): void
    {
        // 1. Arrange: Create an old pipeline run (35 days old) that has already been cleaned
        $oldDate = Carbon::now()->subDays(35)->toDateTimeString();
        PipelineRun::create([
            'service_id' => 'service-123',
            'ingestion_hash' => 'hash-cleaned',
            'schema_version' => 1,
            'meta' => [
                'branch' => 'main',
                'commit_hash' => 'commit-cleaned',
            ],
            'ingested_at' => $oldDate,
            'is_cleaned' => true,
            'runs' => [
                [
                    'tool' => ['key' => 'trivy', 'category' => 'SCA'],
                    'findings' => [
                        [
                            'fingerprint' => 'fp-3',
                            'severity' => 'CRITICAL',
                            'title' => 'Cleaned vuln',
                            'description' => null,
                            'details' => null,
                        ],
                    ],
                ],
            ],
        ]);

        // 2. Act & Assert: Run command and verify it reports 0 runs cleaned
        $exitCode = Artisan::call('app:cleanup-retention-data');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Totaal opgeschoonde runs: 0', $output);
    }

    public function test_configurable_retention_days(): void
    {
        // 1. Arrange: Create a run that is 20 days old
        $date = Carbon::now()->subDays(20)->toDateTimeString();
        $run = PipelineRun::create([
            'service_id' => 'service-123',
            'ingestion_hash' => 'hash-config',
            'schema_version' => 1,
            'meta' => [
                'branch' => 'main',
                'commit_hash' => 'commit-config',
            ],
            'ingested_at' => $date,
            'runs' => [
                [
                    'tool' => ['key' => 'trivy', 'category' => 'SCA'],
                    'findings' => [
                        [
                            'fingerprint' => 'fp-4',
                            'severity' => 'MEDIUM',
                            'title' => 'Medium Vulnerability',
                            'description' => 'Some details...',
                            'details' => ['line' => 10],
                        ],
                    ],
                ],
            ],
        ]);

        // Pre-condition: Using 30 days as default config. 20 days old run is young, so it shouldn't be cleaned.
        $exitCode = Artisan::call('app:cleanup-retention-data');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Totaal opgeschoonde runs: 0', $output);

        $this->assertNull(PipelineRun::find($run->_id)->is_cleaned);

        // 2. Act: Set retention days to 15 (making the 20 days old run eligible for cleanup)
        config(['services.retention.days' => 15]);

        $exitCode = Artisan::call('app:cleanup-retention-data');
        $output = Artisan::output();

        $this->assertEquals(0, $exitCode);
        $this->assertStringContainsString('Totaal opgeschoonde runs: 1', $output);

        // 3. Assert: Verify it was cleaned
        $cleanedRun = PipelineRun::find($run->_id);
        $this->assertTrue($cleanedRun->is_cleaned);
        $this->assertNull($cleanedRun->runs[0]['findings'][0]['description']);
        $this->assertNull($cleanedRun->runs[0]['findings'][0]['details']);
    }
}
