<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Stamp;

use Symfony\Component\Messenger\Stamp\StampInterface;

final readonly class OpenTelemetryTraceStamp implements StampInterface
{
    /**
     * @param array<string, string> $carrier
     */
    public function __construct(
        public array $carrier,
    ) {
    }
}
