<?php

/**
 * Checks normalized-payload.json for blocking findings, excluding snoozed ones.
 *
 * Usage: php check-blocking.php <normalized-payload.json>
 *
 * Required env vars (for fetching snoozed fingerprints from the dashboard):
 *   DASHBOARD_URL                  — base URL of the dashboard API
 *   TARGET_REPO_URL                — repository URL to filter snoozed fingerprints by
 *   ACTIONS_ID_TOKEN_REQUEST_TOKEN — GitHub OIDC token request token (auto-injected by Actions)
 *   ACTIONS_ID_TOKEN_REQUEST_URL   — GitHub OIDC token request URL (auto-injected by Actions)
 *
 * Exit 0 — no blocking findings (or all blocking findings are snoozed)
 * Exit 1 — one or more non-snoozed blocking findings found
 *
 * Blocking rules (matching the original per-job checks):
 *   - gitleaks  : any finding
 *   - trivy     : severity == CRITICAL
 *   - semgrep   : tool_severity == ERROR
 *   - checkov   : severity == CRITICAL or HIGH
 */
$payloadPath = $argv[1] ?? 'normalized-payload.json';

if (! file_exists($payloadPath)) {
    fwrite(STDERR, "Error: '{$payloadPath}' not found.\n");
    exit(1);
}

$snoozedSet = array_flip(fetchSnoozedFingerprints());

$payload = json_decode(file_get_contents($payloadPath), true);

if (json_last_error() !== JSON_ERROR_NONE) {
    fwrite(STDERR, "Error: could not parse '{$payloadPath}': ".json_last_error_msg()."\n");
    exit(1);
}

$blocking = [];
$snoozedCount = 0;

foreach ($payload['runs'] ?? [] as $run) {
    $toolKey = $run['tool']['key'] ?? 'unknown';

    foreach ($run['findings'] ?? [] as $finding) {
        $severity = strtoupper($finding['severity'] ?? '');
        $toolSeverity = strtoupper($finding['tool_severity'] ?? '');
        $fingerprint = $finding['fingerprint'] ?? null;
        $title = $finding['title'] ?? $finding['reference_id'] ?? 'unknown';

        $isBlocking = match ($toolKey) {
            'gitleaks' => true,
            'trivy' => $severity === 'CRITICAL',
            'semgrep', 'semgrep oss' => $toolSeverity === 'ERROR',
            'checkov' => in_array($severity, ['CRITICAL', 'HIGH'], true),
            default => ($severity === 'CRITICAL' || ($finding['type'] ?? '') === 'secret'),
        };

        if (! $isBlocking) {
            continue;
        }

        if ($fingerprint !== null && isset($snoozedSet[$fingerprint])) {
            echo "  [SNOOZED]  [{$toolKey}] {$title}\n";
            $snoozedCount++;

            continue;
        }

        $blocking[] = ['tool' => $toolKey, 'title' => $title, 'fingerprint' => $fingerprint];
    }
}

if ($snoozedCount > 0) {
    echo "\n{$snoozedCount} blocking finding(s) skipped due to active snooze.\n";
}

if ($blocking === []) {
    echo "\n No blocking findings after snooze exemptions.\n";
    exit(0);
}

echo "\n Blocking findings found:\n";
foreach ($blocking as $b) {
    echo "[{$b['tool']}] {$b['title']}\n";
}
echo "\nTotal: ".count($blocking)." blocking finding(s) must be resolved before merging.\n";
exit(1);

function fetchSnoozedFingerprints(): array
{
    $dashboardUrl = getenv('DASHBOARD_URL');
    $tokenRequestToken = getenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN');
    $tokenRequestUrl = getenv('ACTIONS_ID_TOKEN_REQUEST_URL');
    $repoUrl = getenv('TARGET_REPO_URL');

    if (! $dashboardUrl || ! $tokenRequestToken || ! $tokenRequestUrl || ! $repoUrl) {
        fwrite(STDERR, "Warning: Missing env vars for fetching snoozed fingerprints — assuming none.\n");

        return [];
    }

    $audience = getenv('OIDC_AUDIENCE') ?: 'security-dashboard';
    $ch = curl_init("{$tokenRequestUrl}&audience={$audience}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$tokenRequestToken}"],
    ]);
    $tokenResponse = curl_exec($ch);
    $tokenStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($tokenStatus !== 200) {
        fwrite(STDERR, "Warning: Failed to obtain OIDC token (HTTP {$tokenStatus}) — assuming no snoozed fingerprints.\n");

        return [];
    }

    $token = json_decode($tokenResponse, true)['value'] ?? null;
    if (! $token) {
        fwrite(STDERR, "Warning: Could not parse OIDC token — assuming no snoozed fingerprints.\n");

        return [];
    }

    $url = rtrim($dashboardUrl, '/').'/api/snoozed-fingerprints?'.http_build_query(['repository_url' => $repoUrl]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status !== 200) {
        fwrite(STDERR, "Warning: Failed to fetch snoozed fingerprints (HTTP {$status}) — assuming none.\n");

        return [];
    }

    $data = json_decode($response, true);
    $fingerprints = $data['fingerprints'] ?? [];
    echo 'Snoozed findings count: '.count($fingerprints)."\n";

    return $fingerprints;
}
