<?php

namespace App\Services;

use App\Livewire\TrendAnalysis;
use Spatie\Browsershot\Browsershot;
use Spatie\LaravelPdf\Facades\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExportService
{
    public static function generateTrendAnalysisPdf(TrendAnalysis $component): StreamedResponse
    {
        $data = [
            'scanTypeFilter' => $component->scanTypeFilter,
            'newFindingsGranularity' => $component->newFindingsGranularity,
            'openFindingsGranularity' => $component->openFindingsGranularity,
            'openFindingsMonthsBack' => $component->openFindingsMonthsBack,
            'criticalFindingsMonthsBack' => $component->criticalFindingsMonthsBack,
            'trendGranularity' => $component->trendGranularity,
            'velocityGranularity' => $component->velocityGranularity,
            'mttrMonthsBack' => $component->mttrMonthsBack,
            'compareRepoA' => $component->compareRepoA,
            'compareRepoB' => $component->compareRepoB,

            'overallSecurityScore' => $component->overallSecurityScore,
            'kpiSummary' => $component->kpiSummary,
            'riskScoreByRepository' => $component->riskScoreByRepository,
            'findingsVelocity' => $component->findingsVelocity,
            'slaAging' => $component->slaAging,
            'monthlyOpenFindings' => $component->monthlyOpenFindings,
            'criticalFindingsTrend' => $component->criticalFindingsTrend,
            'findingsByTool' => $component->findingsByTool,
            'findingsByPurpose' => $component->findingsByPurpose,
            'mttrByMonth' => $component->mttrByMonth,
            'newFindingsByPeriod' => $component->newFindingsByPeriod,
            'scanFreshness' => $component->scanFreshness,
            'metricsA' => $component->metricsA,
            'metricsB' => $component->metricsB,
            'services' => $component->services,
            'scanTypeLabel' => $component->scanTypeLabel,
            'trendComparisonLabel' => $component->trendComparisonLabel,
            'velocityChartUi' => $component->velocityChartUi,
        ];

        return response()->streamDownload(function () use ($data): void {
            echo Pdf::view('pdf.trend-analysis', $data)
                ->format('A4')
                ->landscape()
                ->margins(0, 0, 0, 0)
                ->withBrowsershot(function (Browsershot $browsershot): void {
                    $browsershot->delay(2500)
                        ->noSandbox()
                        ->deviceScaleFactor(2) // retina resolution for ultra-sharp text and charts
                        ->windowSize(1600, 1200);
                })
                ->generatePdfContent();
        }, 'trendanalyse-rapport-'.date('Y-m-d').'.pdf');
    }
}
