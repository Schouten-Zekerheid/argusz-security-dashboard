<?php

namespace App\Support;

use App\Enums\Severity;

class FindingMapper
{
    /**
     * Maps a raw finding array to a view-ready array with severity styling,
     * source repository links, and status info.
     *
     * @param  array<string, int>  $prevFingerprints  Flipped fingerprint lookup map
     *                                                from the previous run.
     * @param  array<string, string>  $findingStatusIds  Fingerprint → status ID map.
     */
    public static function map(
        array $finding,
        array $prevFingerprints,
        bool $hasPrevRun,
        ?string $commitHash,
        string $repoUrl,
        bool $isGitHub,
        array $findingStatusIds,
    ): array {
        $severity = Severity::fromValue($finding['severity'] ?? null, Severity::Info);
        $details = $finding['details'] ?? [];
        $fingerprint = $finding['fingerprint'] ?? null;
        $filePath = $details['file_path'] ?? null;
        $lineStart = $details['line_start'] ?? null;

        return [
            'severity' => $severity->value,
            'sev_text' => $severity->textClass(),
            'sev_bg' => $severity->panelClass(),
            'reference_id' => $finding['reference_id'] ?? null,
            'title' => $finding['title'] ?? $finding['reference_id'] ?? 'Unknown',
            'description' => $finding['description'] ?? null,
            'file_path' => $filePath,
            'line_start' => $lineStart,
            'line_end' => $details['line_end'] ?? null,
            'package_name' => $details['package_name'] ?? null,
            'installed_version' => $details['installed_version'] ?? null,
            'fixed_version' => $details['fixed_version'] ?? null,
            'is_new' => $hasPrevRun
                && $fingerprint !== null
                && ! isset($prevFingerprints[$fingerprint]),
            'github_url' => $isGitHub && $commitHash && $filePath
                ? RepositoryUrl::fileUrl($repoUrl, $commitHash, (string) $filePath, $lineStart)
                : null,
            'finding_status_id' => $fingerprint !== null
                ? ($findingStatusIds[$fingerprint] ?? null)
                : null,
        ];
    }
}
