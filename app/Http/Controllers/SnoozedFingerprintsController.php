<?php

namespace App\Http\Controllers;

use App\Models\FindingStatus;
use App\Models\Service;
use App\Repositories\ServiceRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class SnoozedFingerprintsController extends Controller
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $repositoryUrl = $request->query('repository_url');

        if (! $repositoryUrl) {
            throw new UnprocessableEntityHttpException(
                'Missing required query parameter: repository_url',
            );
        }

        $service = $this->serviceRepository->findByRepositoryUrl($repositoryUrl);

        if (! $service instanceof Service) {
            throw new UnprocessableEntityHttpException(
                'Service not found for the given repository_url',
            );
        }

        $fingerprints = FindingStatus::where('service_id', (string) $service->_id)
            ->where('current_status', 'snoozed')
            ->pluck('fingerprint')
            ->filter()
            ->values()
            ->all();

        return response()->json(['fingerprints' => $fingerprints]);
    }
}
