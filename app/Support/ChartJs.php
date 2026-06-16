<?php

namespace App\Support;

class ChartJs
{
    public static function legend(string $variant = 'hidden'): array
    {
        return match ($variant) {
            'top' => config('chartjs.legend.top'),
            'bottom' => config('chartjs.legend.bottom'),
            default => config('chartjs.legend.hidden'),
        };
    }

    /**
     * @param  mixed  $yStepSize  omit, null, '' or int — integer steps on y when set
     * @param  mixed  $xMaxTicksLimit  optional cap voor x-as labels (dense datasets)
     */
    public static function scales(
        mixed $stacked = false,
        mixed $xTickFontSize = 11,
        mixed $yTickFontSize = 11,
        mixed $yStepSize = null,
        mixed $beginAtZero = false,
        mixed $xMaxTicksLimit = null,
    ): array {
        $stacked = filter_var($stacked, FILTER_VALIDATE_BOOLEAN);
        $xTickFontSize = (int) ($xTickFontSize ?? 11);
        $yTickFontSize = (int) ($yTickFontSize ?? 11);
        $resolvedStep = ($yStepSize !== null && $yStepSize !== '') ? (int) $yStepSize : null;
        $beginAtZero = filter_var($beginAtZero, FILTER_VALIDATE_BOOLEAN);
        $resolvedMaxTicks = ($xMaxTicksLimit !== null && $xMaxTicksLimit !== '')
            ? (int) $xMaxTicksLimit
            : null;

        $tickColor = config('chartjs.scales.tick_color');
        $gridColor = config('chartjs.scales.grid_color');

        $xScale = [
            'ticks' => [
                'color' => $tickColor,
                'font' => ['size' => $xTickFontSize],
            ],
            'grid' => ['color' => $gridColor],
        ];
        if ($stacked) {
            $xScale['stacked'] = true;
        }
        if ($resolvedMaxTicks !== null) {
            $xScale['ticks']['autoSkip'] = true;
            $xScale['ticks']['maxTicksLimit'] = $resolvedMaxTicks;
        }

        $yTicks = [
            'color' => $tickColor,
            'font' => ['size' => $yTickFontSize],
        ];
        if ($resolvedStep !== null) {
            $yTicks['stepSize'] = $resolvedStep;
        }

        $yScale = [
            'ticks' => $yTicks,
            'grid' => ['color' => $gridColor],
        ];
        if ($stacked) {
            $yScale['stacked'] = true;
        }
        if ($beginAtZero) {
            $yScale['beginAtZero'] = true;
        }

        return ['x' => $xScale, 'y' => $yScale];
    }
}
