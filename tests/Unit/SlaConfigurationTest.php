<?php

namespace Tests\Unit;

use Tests\TestCase;

class SlaConfigurationTest extends TestCase
{
    private ?array $originalSla = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSla = config('sla');
    }

    protected function tearDown(): void
    {
        if ($this->originalSla !== null) {
            config(['sla' => $this->originalSla]);
        }
        parent::tearDown();
    }

    public function test_sla_config_has_correct_defaults(): void
    {
        $this->assertEquals(7, config('sla.critical'));
        $this->assertEquals(30, config('sla.high'));
        $this->assertEquals(90, config('sla.medium'));
        $this->assertEquals(180, config('sla.low'));
    }

    public function test_sla_config_is_mutable(): void
    {
        config(['sla.critical' => 14]);
        config(['sla.high' => 45]);
        config(['sla.medium' => 120]);
        config(['sla.low' => 365]);

        $this->assertEquals(14, config('sla.critical'));
        $this->assertEquals(45, config('sla.high'));
        $this->assertEquals(120, config('sla.medium'));
        $this->assertEquals(365, config('sla.low'));
    }
}
