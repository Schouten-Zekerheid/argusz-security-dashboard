<?php

namespace App\Support;

class RepositoryUrl
{
    public static function isSupported(?string $repositoryUrl): bool
    {
        return config('integrations.scm.provider') === 'github'
            && GitHubUrl::extractOwnerRepo($repositoryUrl) !== null;
    }

    public static function ownerRepo(?string $repositoryUrl): ?string
    {
        if (config('integrations.scm.provider') !== 'github') {
            return null;
        }

        return GitHubUrl::extractOwnerRepo($repositoryUrl);
    }

    public static function fileUrl(
        string $repositoryUrl,
        string $commitHash,
        string $filePath,
        mixed $lineStart = null,
    ): ?string {
        if (! self::isSupported($repositoryUrl)) {
            return null;
        }

        $url = rtrim($repositoryUrl, '/').'/blob/'.$commitHash.'/'.ltrim($filePath, '/');

        if (is_numeric($lineStart)) {
            $url .= '#L'.$lineStart;
        }

        return $url;
    }
}
