<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private readonly ?string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.teams.webhook_url');
    }

    public function criticalFindingsEnabled(): bool
    {
        return config('integrations.notifications.critical_findings_enabled') === true
            && config('integrations.notifications.provider') === 'teams'
            && filled($this->webhookUrl);
    }

    /**
     * Send a notification to Teams for a critical finding.
     */
    public function notifyCriticalFinding(array $finding, string $serviceName): void
    {
        if (! $this->criticalFindingsEnabled()) {
            return;
        }

        $title = $finding['title'] ?? $finding['reference_id'] ?? 'Onbekende bevinding';
        $severity = strtoupper($finding['severity'] ?? 'UNKNOWN');
        $tool = is_array($finding['tool']) ? ($finding['tool']['key'] ?? 'onbekend') : ($finding['tool'] ?? 'onbekend');
        $url = $finding['argusz_url'] ?? route('findings.show', ['id' => (string) ($finding['_id'] ?? $finding['fingerprint'])]);

        $payload = [
            '@type' => 'MessageCard',
            '@context' => 'http://schema.org/extensions',
            'themeColor' => 'FF0000', // Rood voor kritiek
            'summary' => "Kritieke bevinding in {$serviceName}",
            'sections' => [
                [
                    'activityTitle' => "🚨 Kritieke bevinding gedetecteerd in **{$serviceName}**",
                    'activitySubtitle' => "Tool: {$tool}",
                    'facts' => [
                        ['name' => 'Titel', 'value' => $title],
                        ['name' => 'Severity', 'value' => $severity],
                        ['name' => 'Type', 'value' => $finding['type'] ?? '—'],
                        ['name' => 'Referentie', 'value' => $finding['reference_id'] ?? '—'],
                        ['name' => 'Link', 'value' => "[Bekijk in Argusz]({$url})"],
                    ],
                    'markdown' => true,
                ],
            ],
            'potentialAction' => [
                [
                    '@type' => 'OpenUri',
                    'name' => 'Bekijk in '.config('app.name'),
                    'targets' => [
                        ['os' => 'default', 'uri' => $url],
                    ],
                ],
            ],
        ];

        try {
            $response = Http::post($this->webhookUrl, $payload);

            if (! $response->successful()) {
                Log::error('Error sending notification webhook', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Exception sending notification webhook', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
