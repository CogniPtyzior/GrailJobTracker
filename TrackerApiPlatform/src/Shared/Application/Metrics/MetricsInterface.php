<?php

declare(strict_types=1);

namespace App\Shared\Application\Metrics;

interface MetricsInterface
{
    /**
     * @param array<string, scalar> $attributes
     */
    public function increment(
        string $name,
        int|float $value = 1,
        array $attributes = [],
    ): void;
}
