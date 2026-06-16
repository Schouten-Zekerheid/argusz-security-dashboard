<?php

namespace App\Enums;

enum Severity: string
{
    case Critical = 'CRITICAL';
    case High = 'HIGH';
    case Medium = 'MEDIUM';
    case Low = 'LOW';
    case Info = 'INFO';
    case Unknown = 'UNKNOWN';

    public static function fromValue(mixed $value, self $fallback = self::Unknown): self
    {
        if (is_array($value)) {
            $value = collect($value)
                ->filter(fn ($item): bool => ! is_null($item) && $item !== '')
                ->first();
        }

        if (! is_scalar($value) || $value === '') {
            return $fallback;
        }

        return self::tryFrom(strtoupper((string) $value)) ?? $fallback;
    }

    /**
     * @return list<string>
     */
    public static function ingestValues(): array
    {
        return array_map(
            fn (self $severity): string => $severity->value,
            self::knownCases(),
        );
    }

    /**
     * @return list<self>
     */
    public static function knownCases(): array
    {
        return [
            self::Critical,
            self::High,
            self::Medium,
            self::Low,
            self::Info,
        ];
    }

    /**
     * @return list<self>
     */
    public static function riskCases(): array
    {
        return [
            self::Critical,
            self::High,
            self::Medium,
            self::Low,
        ];
    }

    /**
     * @return array<string, int>
     */
    public static function zeroCounts(bool $includeInfo = false): array
    {
        $cases = $includeInfo ? self::knownCases() : self::riskCases();

        return array_fill_keys(
            array_map(fn (self $severity): string => $severity->value, $cases),
            0,
        );
    }

    /**
     * @return list<array{label: string, key: string, color: string, text: string}>
     */
    public static function breakdownRows(bool $includeInfo = true): array
    {
        $cases = $includeInfo ? self::knownCases() : self::riskCases();

        return array_map(fn (self $severity): array => [
            'label' => $severity->label(),
            'key' => $severity->value,
            'color' => $severity->dotClass(),
            'text' => $severity->textClass(),
        ], $cases);
    }

    public function label(): string
    {
        return match ($this) {
            self::Critical => 'Critical',
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
            self::Info => 'Info',
            self::Unknown => 'Unknown',
        };
    }

    public function sortOrder(): int
    {
        return match ($this) {
            self::Critical => 0,
            self::High => 1,
            self::Medium => 2,
            self::Low => 3,
            self::Info => 4,
            self::Unknown => 99,
        };
    }

    public function sortWeight(): int
    {
        return match ($this) {
            self::Critical => 4,
            self::High => 3,
            self::Medium => 2,
            self::Low => 1,
            self::Info,
            self::Unknown => 0,
        };
    }

    public function riskScoreWeight(): int
    {
        return match ($this) {
            self::Critical => 10,
            self::High => 5,
            self::Medium => 2,
            self::Low => 1,
            self::Info,
            self::Unknown => 0,
        };
    }

    public function textClass(): string
    {
        return match ($this) {
            self::Critical => 'text-severity-critical',
            self::High => 'text-severity-high',
            self::Medium => 'text-severity-medium',
            self::Low => 'text-severity-low',
            self::Info,
            self::Unknown => 'text-severity-info',
        };
    }

    public function dotClass(): string
    {
        return match ($this) {
            self::Critical => 'bg-severity-critical',
            self::High => 'bg-severity-high',
            self::Medium => 'bg-severity-medium',
            self::Low => 'bg-severity-low',
            self::Info,
            self::Unknown => 'bg-severity-info',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Critical => 'bg-red-500/20 text-red-200 ring-1 ring-red-400/70',
            self::High => 'bg-orange-500/20 text-orange-200 ring-1 ring-orange-400/70',
            self::Medium => 'bg-amber-500/20 text-amber-200 ring-1 ring-amber-400/60',
            self::Low => 'bg-lime-500/20 text-lime-200 ring-1 ring-lime-400/60',
            self::Info,
            self::Unknown => 'bg-slate-700/40 text-slate-200 ring-1 ring-slate-500/60',
        };
    }

    public function panelClass(): string
    {
        return match ($this) {
            self::Critical => 'bg-red-500/10 ring-red-500/20',
            self::High => 'bg-orange-500/10 ring-orange-500/20',
            self::Medium => 'bg-amber-500/10 ring-amber-500/20',
            self::Low => 'bg-lime-500/10 ring-lime-500/20',
            self::Info,
            self::Unknown => 'bg-blue-500/10 ring-blue-500/20',
        };
    }

    public function chartColor(): string
    {
        return match ($this) {
            self::Critical => 'rgba(239, 68, 68, 0.75)',
            self::High => 'rgba(249, 115, 22, 0.75)',
            self::Medium => 'rgba(251, 191, 36, 0.75)',
            self::Low => 'rgba(163, 230, 53, 0.75)',
            self::Info,
            self::Unknown => 'rgba(56, 189, 248, 0.75)',
        };
    }

    public function jiraPriority(): string
    {
        return match ($this) {
            self::Critical => 'Highest',
            self::High => 'High',
            self::Medium,
            self::Unknown => 'Medium',
            self::Low => 'Low',
            self::Info => 'Lowest',
        };
    }
}
