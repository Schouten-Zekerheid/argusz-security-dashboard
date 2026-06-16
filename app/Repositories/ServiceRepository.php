<?php

namespace App\Repositories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository
{
    /**
     * @return Collection<int, Service>
     *
     * @phpstan-return Collection<int, Service>
     */
    public function scanTargets(?string $type = null): Collection
    {
        $query = Service::where('active', true);

        if ($type === 'container') {
            $query->whereNotNull('image_ref')->where('image_ref', '!=', '');
        }

        /** @var Collection<int, Service> $services */
        $services = $query->get(['name', 'repository_url', 'default_branch', 'image_ref']);

        return $services;
    }

    public function findByRepositoryUrl(string $url): ?Service
    {
        return Service::where('repository_url', $url)->first();
    }

    public function findByImageRef(string $imageRef): ?Service
    {
        $clean = $this->cleanImageRef($imageRef);

        // 1. Try exact match
        $service = Service::where('image_ref', $imageRef)
            ->orWhere('image_ref', $clean)
            ->first();

        if ($service) {
            return $service;
        }

        // 4. Fallback: compare cleaned versions of all services with an image_ref
        $services = Service::whereNotNull('image_ref')->get();
        foreach ($services as $s) {
            if ($this->cleanImageRef($s->image_ref) === $clean) {
                return $s;
            }
        }

        return null;
    }

    private function cleanImageRef(string $imageRef): string
    {
        if (str_contains($imageRef, '/')) {
            $imageRef = substr($imageRef, strpos($imageRef, '/') + 1);
        }
        if (str_contains($imageRef, ':')) {
            return substr($imageRef, 0, strpos($imageRef, ':'));
        }

        return $imageRef;
    }
}
