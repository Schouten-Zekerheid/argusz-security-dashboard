<?php

$resultsDir = rtrim($argv[1] ?? 'scan-results', '/');

if (! is_dir($resultsDir) && ! file_exists($resultsDir)) {
    fwrite(STDERR, "Error: path '{$resultsDir}' does not exist.\n");
    exit(1);
}

$scanMode = getEnvVar('SCAN_MODE', 'tier1');
$imageRef = getEnvVar('IMAGE_REF');

$repo = getEnvVar('TARGET_REPO') ?? getEnvVar('GITHUB_REPOSITORY') ?? 'unknown/unknown';
$service = getEnvVar('TARGET_SERVICE') ?? basename($repo);
$repository_url = getEnvVar('TARGET_REPO_URL') ?? 'https://github.com/'.$repo;
$branch = getEnvVar('TARGET_BRANCH', 'unknown');
$environment = getEnvVar('TARGET_ENVIRONMENT') ?? getEnvVar('GITHUB_REF_NAME') ?? 'unknown';
$commit = getEnvVar('TARGET_SHA', 'unknown');
$actor = getEnvVar('TARGET_ACTOR', 'unknown');
$prNumber = getEnvVar('TARGET_PR_NUMBER');
$timestamp = date('c');

// Find all JSON/SARIF files in the results directory or use the path directly if it is a file
$files = [];
if (is_dir($resultsDir)) {
    $dirIterator = new RecursiveDirectoryIterator($resultsDir);
    $iterator = new RecursiveIteratorIterator($dirIterator);
    foreach ($iterator as $file) {
        if ($file->isFile() && in_array(pathinfo($file->getPathname(), PATHINFO_EXTENSION), ['sarif', 'json'])) {
            $files[] = $file->getPathname();
        }
    }
} elseif (is_file($resultsDir)) {
    $files[] = $resultsDir;
}

$runs = [];

foreach ($files as $filePath) {
    $data = readJson($filePath);
    if ($data === null) {
        continue;
    }

    if (! isSarif($data)) {
        fwrite(STDERR, "Info: Skipping file '{$filePath}' (not a valid SARIF v2.1.0 document).\n");

        continue;
    }

    foreach ($data['runs'] ?? [] as $run) {
        $toolNameRaw = $run['tool']['driver']['name'] ?? 'unknown';
        $toolVersion = $run['tool']['driver']['version'] ?? null;
        $toolKey = strtolower($toolNameRaw);

        $metadata = detectToolMetadata($toolNameRaw, basename($filePath), $scanMode);
        $category = $metadata['category'];
        $scanType = $metadata['scanType'];
        $findingType = $metadata['findingType'];

        $findings = parseSarifRun($run, $toolKey, $findingType, $timestamp);

        $runs[] = [
            'tool' => array_filter([
                'key' => $toolKey,
                'category' => $category,
                'version' => $toolVersion,
            ], fn ($v) => $v !== null),
            'scan' => [
                'type' => $scanType,
                'status' => 'success',
                'artifact_ref' => basename($filePath),
            ],
            'findings' => $findings,
        ];
    }
}

$payload = [
    'schema_version' => 1,
    'meta' => array_filter([
        'service' => $service,
        'repository_url' => $repository_url,
        'branch' => $branch,
        'environment' => $environment,
        'repository' => $repo,
        'commit_hash' => $commit,
        'actor' => $actor,
        'timestamp' => $timestamp,
        'tier' => $scanMode === 'container_image' ? 'container' : '1',
        'image_ref' => $imageRef,
    ], fn ($v) => $v !== null),

    'runs' => $runs,
];

$json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

file_put_contents('normalized-payload.json', $json);

$dashboardUrl = getenv('DASHBOARD_URL');
$tokenRequestToken = getenv('ACTIONS_ID_TOKEN_REQUEST_TOKEN');
$tokenRequestUrl = getenv('ACTIONS_ID_TOKEN_REQUEST_URL');

if ($dashboardUrl && $tokenRequestToken && $tokenRequestUrl) {
    $audience = getEnvVar('OIDC_AUDIENCE', 'security-dashboard');
    $ch = curl_init("{$tokenRequestUrl}&audience={$audience}");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["Authorization: Bearer {$tokenRequestToken}"],
    ]);
    $tokenResponse = curl_exec($ch);
    $tokenStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($tokenStatus !== 200) {
        fwrite(STDERR, "Error: Failed to obtain OIDC token (HTTP {$tokenStatus}).\n");
        exit(1);
    }

    $token = json_decode($tokenResponse, true)['value'] ?? null;
    if (! $token) {
        fwrite(STDERR, "Error: Could not parse OIDC token.\n");
        exit(1);
    }

    $ch = curl_init(rtrim($dashboardUrl, '/').'/api/ingest');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            'Content-Type: application/json',
            'Accept: application/json',
        ],
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($status < 200 || $status >= 300) {
        fwrite(STDERR, "Error: Failed to push to dashboard (HTTP {$status}): {$response}\n");
        exit(1);
    }

    echo "Pushed to dashboard (HTTP {$status}).\n";
} else {
    echo "Skipping push: DASHBOARD_URL or OIDC env vars not set.\n";
}

function readJson(string $path): ?array
{
    if (! file_exists($path)) {
        return null;
    }

    $raw = file_get_contents($path);
    $decoded = json_decode($raw, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        fwrite(STDERR, "Warning: could not parse JSON from '{$path}': ".json_last_error_msg()."\n");

        return null;
    }

    return $decoded;
}

function isSarif(array $data): bool
{
    return isset($data['version']) && $data['version'] === '2.1.0' && isset($data['runs']) && is_array($data['runs']);
}

function fingerprint(string ...$parts): string
{
    return 'sha256:'.implode(':', $parts);
}

function stripScanPrefix(string $path): string
{
    return preg_replace('#^external-code/#', '', $path);
}

function normalizeSeverity(string $raw): string
{
    $raw = strtoupper(trim($raw));

    return match ($raw) {
        'CRITICAL' => 'CRITICAL',
        'HIGH' => 'HIGH',
        'MEDIUM' => 'MEDIUM',
        'LOW' => 'LOW',
        'INFO' => 'INFO',
        'UNKNOWN' => 'INFO',
        'NEGLIGIBLE' => 'LOW',
        default => 'INFO',
    };
}

function getEnvVar(string $name, ?string $default = null): ?string
{
    $val = getenv($name);

    return ($val === false || $val === '') ? $default : $val;
}

function parseSarifRun(array $run, string $toolKey, string $findingType, string $timestamp): array
{
    $findings = [];
    $allowedSeveritiesRaw = getEnvVar('ALLOWED_SEVERITIES');
    $allowedSeverities = null;
    if ($allowedSeveritiesRaw) {
        $allowedSeverities = array_map('trim', explode(',', strtoupper($allowedSeveritiesRaw)));
    }

    // Map rules by ID for easy metadata lookup
    $rulesMap = [];
    foreach ($run['tool']['driver']['rules'] ?? [] as $rule) {
        if (isset($rule['id'])) {
            $rulesMap[$rule['id']] = $rule;
        }
    }

    foreach ($run['results'] ?? [] as $result) {
        $ruleId = $result['ruleId'] ?? 'unknown';
        $rule = $rulesMap[$ruleId] ?? null;

        // Severity mapping
        [$severity, $toolSeverity] = getSarifSeverity($result, $rule);

        // Secrets are always critical
        if ($findingType === 'secret') {
            $severity = 'CRITICAL';
        }

        if ($allowedSeverities !== null && ! in_array($severity, $allowedSeverities, true)) {
            continue;
        }

        // Retrieve location details
        $location = $result['locations'][0] ?? null;
        $filePath = 'unknown';
        $lineStart = 0;
        $lineEnd = 0;
        $columnStart = 0;
        $columnEnd = 0;

        if ($location && isset($location['physicalLocation'])) {
            $phys = $location['physicalLocation'];
            $filePath = stripScanPrefix($phys['artifactLocation']['uri'] ?? 'unknown');
            if (isset($phys['region'])) {
                $region = $phys['region'];
                $lineStart = $region['startLine'] ?? 0;
                $lineEnd = $region['endLine'] ?? $lineStart;
                $columnStart = $region['startColumn'] ?? 0;
                $columnEnd = $region['endColumn'] ?? $columnStart;
            }
        }

        // Titles and descriptions
        $title = $rule['shortDescription']['text'] ?? $result['message']['text'] ?? $rule['name'] ?? $ruleId;
        $description = $rule['fullDescription']['text'] ?? $result['message']['text'] ?? $rule['help']['text'] ?? $title;

        // Fingerprint generation
        $fingerprint = null;
        if (isset($result['partialFingerprints'])) {
            foreach (['fingerprint', 'primaryLocationLineHash', 'gitleaksRuleUniqueKey'] as $keyName) {
                if (! empty($result['partialFingerprints'][$keyName])) {
                    $fingerprint = fingerprint($toolKey, $result['partialFingerprints'][$keyName]);
                    break;
                }
            }
        }
        if (! $fingerprint) {
            $fingerprint = fingerprint($toolKey, $ruleId, $filePath, (string) $lineStart);
        }

        // Build details object
        $details = array_filter([
            'file_path' => $filePath !== 'unknown' ? $filePath : null,
            'line_start' => $lineStart ?: null,
            'line_end' => $lineEnd ?: null,
            'column_start' => $columnStart ?: null,
            'column_end' => $columnEnd ?: null,
            'rule_id' => $ruleId,
            'rule_name' => $rule['name'] ?? null,
            'help_uri' => $rule['helpUri'] ?? null,
        ], fn ($v) => $v !== null);

        if (isset($location['logicalLocations'][0]['fullyQualifiedName'])) {
            $details['resource'] = $location['logicalLocations'][0]['fullyQualifiedName'];
        }

        if (! empty($result['properties'])) {
            $details['properties'] = $result['properties'];
        }

        $findings[] = [
            'type' => $findingType,
            'severity' => $severity,
            'tool_severity' => $toolSeverity,
            'reference_id' => $ruleId,
            'title' => $title,
            'description' => $description,
            'fingerprint' => $fingerprint,
            'first_seen_at' => $timestamp,
            'details' => $details ?: null,
        ];
    }

    return $findings;
}

function getSarifSeverity(array $result, ?array $rule): array
{
    $toolSeverity = 'N/A';

    // 1. Check rule security-severity property (CVSS score)
    $securitySeverity = $rule['properties']['security-severity'] ?? null;
    if ($securitySeverity !== null && is_numeric($securitySeverity)) {
        $val = (float) $securitySeverity;
        $toolSeverity = (string) $val;
        if ($val >= 9.0) {
            return ['CRITICAL', $toolSeverity];
        } elseif ($val >= 7.0) {
            return ['HIGH', $toolSeverity];
        } elseif ($val >= 4.0) {
            return ['MEDIUM', $toolSeverity];
        } elseif ($val >= 0.1) {
            return ['LOW', $toolSeverity];
        } else {
            return ['INFO', $toolSeverity];
        }
    }

    // 2. Check rule severity property (string)
    $severityProp = $rule['properties']['severity'] ?? null;
    if ($severityProp !== null && is_string($severityProp)) {
        $toolSeverity = $severityProp;

        return [normalizeSeverity($severityProp), $toolSeverity];
    }

    // 3. Fallback to result level or rule default level
    $level = $result['level'] ?? $rule['defaultConfiguration']['level'] ?? 'warning';
    $toolSeverity = $level;
    $mapped = match (strtolower(trim($level))) {
        'error' => 'HIGH',
        'warning' => 'MEDIUM',
        'note' => 'LOW',
        'none' => 'INFO',
        default => 'INFO',
    };

    return [$mapped, $toolSeverity];
}

function detectToolMetadata(string $toolName, string $filename, string $scanMode): array
{
    $toolNameLower = strtolower($toolName);

    // 1. Hardcoded defaults for standard tools to be safe and accurate
    switch ($toolNameLower) {
        case 'trivy':
            return [
                'category' => 'SCA',
                'scanType' => ($scanMode === 'container_image') ? 'container_image' : 'filesystem',
                'findingType' => 'vulnerability',
            ];
        case 'gitleaks':
            return [
                'category' => 'SECRETS',
                'scanType' => 'repository',
                'findingType' => 'secret',
            ];
        case 'semgrep':
        case 'semgrep oss':
            return [
                'category' => 'SAST',
                'scanType' => 'source',
                'findingType' => 'code_issue',
            ];
        case 'checkov':
            return [
                'category' => 'IaC',
                'scanType' => 'infrastructure',
                'findingType' => 'iac_misconfiguration',
            ];
    }

    // 2. Check filename for hints (e.g. `snyk.sca.sarif` or `sobelow-sast.sarif`)
    if (preg_match('/[._-](sca|secrets?|sast|iac)[._-]/i', $filename, $matches)) {
        $hint = strtolower($matches[1]);
        if ($hint === 'sca') {
            return [
                'category' => 'SCA',
                'scanType' => ($scanMode === 'container_image') ? 'container_image' : 'filesystem',
                'findingType' => 'vulnerability',
            ];
        } elseif ($hint === 'secrets' || $hint === 'secret') {
            return [
                'category' => 'SECRETS',
                'scanType' => 'repository',
                'findingType' => 'secret',
            ];
        } elseif ($hint === 'sast') {
            return [
                'category' => 'SAST',
                'scanType' => 'source',
                'findingType' => 'code_issue',
            ];
        } elseif ($hint === 'iac') {
            return [
                'category' => 'IaC',
                'scanType' => 'infrastructure',
                'findingType' => 'iac_misconfiguration',
            ];
        }
    }

    // 3. Keyword matching on tool name
    if (str_contains($toolNameLower, 'leak') || str_contains($toolNameLower, 'secret') || str_contains($toolNameLower, 'trufflehog')) {
        return [
            'category' => 'SECRETS',
            'scanType' => 'repository',
            'findingType' => 'secret',
        ];
    }
    if (str_contains($toolNameLower, 'snyk') || str_contains($toolNameLower, 'audit') || str_contains($toolNameLower, 'dep') || str_contains($toolNameLower, 'retire')) {
        return [
            'category' => 'SCA',
            'scanType' => ($scanMode === 'container_image') ? 'container_image' : 'filesystem',
            'findingType' => 'vulnerability',
        ];
    }
    if (str_contains($toolNameLower, 'tfsec') || str_contains($toolNameLower, 'terrascan') || str_contains($toolNameLower, 'iac') || str_contains($toolNameLower, 'kics')) {
        return [
            'category' => 'IaC',
            'scanType' => 'infrastructure',
            'findingType' => 'iac_misconfiguration',
        ];
    }

    // 4. Default fallback
    return [
        'category' => 'SAST',
        'scanType' => 'source',
        'findingType' => 'code_issue',
    ];
}
