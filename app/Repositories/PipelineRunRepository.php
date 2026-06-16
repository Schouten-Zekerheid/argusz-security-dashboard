<?php

namespace App\Repositories;

use App\Models\PipelineRun;
use App\Models\Service;
use Carbon\Carbon;

class PipelineRunRepository
{
    public function existsByHash(string $hash): bool
    {
        return PipelineRun::where('ingestion_hash', $hash)->exists();
    }

    public function create(Service $service, string $hash, array $payload): PipelineRun
    {
        return PipelineRun::create([
            'service_id' => (string) $service->_id,
            'ingestion_hash' => $hash,
            'schema_version' => (int) $payload['schema_version'],
            'meta' => $payload['meta'],
            'runs' => $payload['runs'],
            'ingested_at' => Carbon::now()->toDateTimeString(),
        ]);
    }

    public function getLatestGithubRun(): ?PipelineRun
    {
        return PipelineRun::where('meta.tier', '!=', 'container')
            ->get(['ingested_at'])
            ->sortByDesc('ingested_at')
            ->first();
    }

    public function getLatestContainerRun(): ?PipelineRun
    {
        return PipelineRun::where('meta.tier', 'container')
            ->get(['ingested_at'])
            ->sortByDesc('ingested_at')
            ->first();
    }
}
