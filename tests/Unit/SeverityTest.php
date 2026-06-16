<?php

namespace Tests\Unit;

use App\Enums\Severity;
use PHPUnit\Framework\TestCase;

class SeverityTest extends TestCase
{
    public function test_ingest_values_exclude_unknown(): void
    {
        $this->assertSame(
            ['CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO'],
            Severity::ingestValues(),
        );
    }

    public function test_from_value_normalizes_or_falls_back(): void
    {
        $this->assertSame(Severity::Critical, Severity::fromValue('critical'));
        $this->assertSame(Severity::High, Severity::fromValue(['', null, 'HIGH']));
        $this->assertSame(Severity::Unknown, Severity::fromValue('EXTREME'));
        $this->assertSame(Severity::Info, Severity::fromValue(null, Severity::Info));
    }

    public function test_risk_score_weights_are_ordered_by_severity(): void
    {
        $this->assertSame(10, Severity::Critical->riskScoreWeight());
        $this->assertSame(5, Severity::High->riskScoreWeight());
        $this->assertSame(2, Severity::Medium->riskScoreWeight());
        $this->assertSame(1, Severity::Low->riskScoreWeight());
        $this->assertSame(0, Severity::Info->riskScoreWeight());
    }
}
