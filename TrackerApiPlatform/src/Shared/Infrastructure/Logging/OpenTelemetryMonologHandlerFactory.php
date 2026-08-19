<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Logging;

use Monolog\Level;
use OpenTelemetry\API\Globals;
use OpenTelemetry\Contrib\Logs\Monolog\Handler;

final class OpenTelemetryMonologHandlerFactory
{
    public static function create(): Handler
    {
        $provider = Globals::loggerProvider();

        error_log('OTEL LoggerProvider: ' . get_debug_type($provider));

        return new Handler(
            $provider,
            'debug',
            true,
        );
    }
}
