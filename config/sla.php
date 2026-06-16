<?php

use App\Services\TrendAnalysisService;

return [
    'critical' => (int) env('SLA_CRITICAL_DAYS', TrendAnalysisService::DEFAULT_CRITICAL_SLA),
    'high' => (int) env('SLA_HIGH_DAYS', TrendAnalysisService::DEFAULT_HIGH_SLA),
    'medium' => (int) env('SLA_MEDIUM_DAYS', TrendAnalysisService::DEFAULT_MEDIUM_SLA),
    'low' => (int) env('SLA_LOW_DAYS', TrendAnalysisService::DEFAULT_LOW_SLA),
];
