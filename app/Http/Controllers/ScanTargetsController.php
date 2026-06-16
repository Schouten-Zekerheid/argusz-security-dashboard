<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Repositories\ServiceRepository;
use App\Support\RepositoryUrl;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScanTargetsController extends Controller
{
    public function __construct(
        private readonly ServiceRepository $serviceRepository,
    ) {}

    /**
     * Returns the list of active services as scan targets for the daily cron workflow.
     *
     * Each entry contains the repository slug and cached default branch so the
     * orchestrator workflow knows which branch to scan without extra SCM API calls.
     */
    public function index(Request $request): JsonResponse
    {
        $queryType = $request->query('type');
        $type = is_string($queryType) ? $queryType : null;

        $targets = $this->serviceRepository
            ->scanTargets($type)
            ->map(function (Service $service) use ($type): ?array {
                $ownerRepo = RepositoryUrl::ownerRepo($service->repository_url);

                if ($ownerRepo === null) {
                    return null;
                }

                $target = [
                    'service' => $service->name,
                    'repository' => $ownerRepo,
                    'repository_url' => $service->repository_url,
                    'default_branch' => $service->default_branch,
                ];

                if ($type === 'container') {
                    $target['image_ref'] = $service->image_ref;
                }

                return $target;
            })
            ->filter()
            ->values();

        return response()->json(['targets' => $targets]);
    }
}
