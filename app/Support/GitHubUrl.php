<?php

namespace App\Support;

class GitHubUrl
{
    /**
     * Extracts "owner/repo" from a full GitHub URL.
     * e.g. "https://github.com/example-org/example-repo" -> "example-org/example-repo"
     */
    public static function extractOwnerRepo(?string $repositoryUrl): ?string
    {
        if ($repositoryUrl === null) {
            return null;
        }

        if (preg_match(
            '#github\.com[/:]([^/]+/[^/]+?)(?:\.git)?$#',
            $repositoryUrl,
            $matches,
        )) {
            return $matches[1];
        }

        return null;
    }
}
