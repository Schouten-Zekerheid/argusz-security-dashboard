<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreScanResultRequest;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Services\IngestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class ScanResultController extends Controller
{
    public function __construct(
        private readonly IngestService $ingestService,
        private readonly ServiceRepository $serviceRepository,
    ) {}

    public function store(StoreScanResultRequest $request): JsonResponse
    {
        $payload = $request->validated();

        $service = null;
        if (! empty($payload['meta']['image_ref'])) {
            $service = $this->serviceRepository
                ->findByImageRef($payload['meta']['image_ref']);
        }

        if (! $service instanceof Service) {
            $service = $this->serviceRepository
                ->findByRepositoryUrl($payload['meta']['repository_url']);
        }

        if (! $service instanceof Service) {
            Log::warning('Ingest geweigerd: service niet gevonden', [
                'repository_url' => $payload['meta']['repository_url'],
                'image_ref' => $payload['meta']['image_ref'] ?? null,
            ]);
            throw new UnprocessableEntityHttpException('Service not found');
        }

        $pipelineRun = $this->ingestService->store($service, $payload);

        return response()->json(['pipeline_run_id' => (string) $pipelineRun->_id], 201);
    }
}
