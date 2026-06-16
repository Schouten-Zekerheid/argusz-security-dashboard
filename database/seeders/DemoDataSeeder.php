<?php

namespace Database\Seeders;

use App\Models\FindingStatus;
use App\Models\PipelineRun;
use App\Models\Service;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/**
 * Seeds example services, pipeline runs, and findings into MongoDB so a fresh
 * install has a populated dashboard to explore. This is NOT part of
 * DatabaseSeeder and never runs automatically — invoke it explicitly:
 *
 *   php artisan db:seed --class=DemoDataSeeder
 *
 * It refuses to run in production to avoid polluting real data.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->error('DemoDataSeeder is disabled in production.');

            return;
        }

        foreach ($this->services() as $definition) {
            $service = Service::create([
                'name' => $definition['name'],
                'repository_url' => $definition['repository_url'],
                'active' => true,
                'default_branch' => 'main',
            ]);

            $run = PipelineRun::create([
                'service_id' => (string) $service->_id,
                'ingestion_hash' => hash('sha256', $definition['name'].Carbon::now()),
                'schema_version' => 1,
                'meta' => [
                    'service' => $definition['name'],
                    'repository_url' => $definition['repository_url'],
                    'branch' => 'main',
                    'environment' => 'production',
                    'repository' => $definition['repository'],
                    'commit_hash' => substr(hash('sha256', $definition['name']), 0, 12),
                    'actor' => 'demo-seeder',
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'tier' => '1',
                ],
                'runs' => [],
                'ingested_at' => Carbon::now()->toDateTimeString(),
            ]);

            foreach ($definition['findings'] as $i => $finding) {
                FindingStatus::create([
                    'service_id' => (string) $service->_id,
                    'pipeline_run_id' => (string) $run->_id,
                    'scan_source' => 'github',
                    'fingerprint' => 'sha256:demo-'.$service->_id.'-'.$i,
                    'current_status' => $finding['status'],
                    'tool' => ['key' => $finding['tool'], 'category' => $finding['category']],
                    'severity' => $finding['severity'],
                    'type' => $finding['type'],
                    'title' => $finding['title'],
                    'reference_id' => $finding['reference_id'],
                    'status_updated_at' => Carbon::now()->toDateTimeString(),
                    'updated_by' => null,
                    'resolution_reason' => null,
                    'snooze_reason' => null,
                    'jira_issue_key' => null,
                    'history' => [],
                ]);
            }
        }

        $this->command?->info('Demo data seeded: 3 services with example findings.');
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        return [
            [
                'name' => 'payments-api',
                'repository' => 'demo-org/payments-api',
                'repository_url' => 'https://github.com/demo-org/payments-api',
                'findings' => [
                    [
                        'tool' => 'trivy', 'category' => 'SCA', 'type' => 'vulnerability',
                        'severity' => 'CRITICAL', 'status' => 'open',
                        'title' => 'Remote code execution in serialization library',
                        'reference_id' => 'CVE-2025-0001',
                    ],
                    [
                        'tool' => 'gitleaks', 'category' => 'SECRETS', 'type' => 'secret',
                        'severity' => 'HIGH', 'status' => 'open',
                        'title' => 'AWS access key committed to source',
                        'reference_id' => 'aws-access-key',
                    ],
                    [
                        'tool' => 'semgrep', 'category' => 'SAST', 'type' => 'code_issue',
                        'severity' => 'MEDIUM', 'status' => 'returning',
                        'title' => 'SQL query built from unsanitized input',
                        'reference_id' => 'sql-injection',
                    ],
                ],
            ],
            [
                'name' => 'web-frontend',
                'repository' => 'demo-org/web-frontend',
                'repository_url' => 'https://github.com/demo-org/web-frontend',
                'findings' => [
                    [
                        'tool' => 'semgrep', 'category' => 'SAST', 'type' => 'code_issue',
                        'severity' => 'MEDIUM', 'status' => 'open',
                        'title' => 'Potential cross-site scripting in template',
                        'reference_id' => 'xss-template',
                    ],
                    [
                        'tool' => 'trivy', 'category' => 'SCA', 'type' => 'vulnerability',
                        'severity' => 'LOW', 'status' => 'open',
                        'title' => 'Outdated transitive dependency',
                        'reference_id' => 'CVE-2025-0042',
                    ],
                ],
            ],
            [
                'name' => 'infra-terraform',
                'repository' => 'demo-org/infra-terraform',
                'repository_url' => 'https://github.com/demo-org/infra-terraform',
                'findings' => [
                    [
                        'tool' => 'checkov', 'category' => 'IaC', 'type' => 'iac_misconfiguration',
                        'severity' => 'HIGH', 'status' => 'resolved',
                        'title' => 'Storage bucket allows public read access',
                        'reference_id' => 'CKV_AWS_20',
                    ],
                ],
            ],
        ];
    }
}
