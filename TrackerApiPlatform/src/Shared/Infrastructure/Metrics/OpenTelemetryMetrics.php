<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Metrics;

use App\Shared\Application\Metrics\MetricsInterface;
use OpenTelemetry\API\Globals;

final class OpenTelemetryMetrics implements MetricsInterface
{
    /** @var array<string, object> */
    private array $counters = [];

    public function increment(
        string $name,
        int|float $value = 1,
        array $attributes = [],
    ): void {
        $counter = $this->counters[$name] ??= Globals::meterProvider()
            ->getMeter('grailjob.application')
            ->createCounter($name);

        $counter->add($value, $attributes);
    }
}
