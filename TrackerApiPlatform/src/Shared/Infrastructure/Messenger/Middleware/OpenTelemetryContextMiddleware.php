<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Messenger\Middleware;

use App\Shared\Infrastructure\Messenger\Stamp\OpenTelemetryTraceStamp;
use OpenTelemetry\API\Globals;
use OpenTelemetry\API\Trace\SpanKind;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Middleware\MiddlewareInterface;
use Symfony\Component\Messenger\Middleware\StackInterface;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;

final readonly class OpenTelemetryContextMiddleware implements MiddlewareInterface
{
    public function handle(
        Envelope $envelope,
        StackInterface $stack,
    ): Envelope {
        if ($envelope->last(ReceivedStamp::class) === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        /** @var OpenTelemetryTraceStamp|null $traceStamp */
        $traceStamp = $envelope->last(OpenTelemetryTraceStamp::class);

        if ($traceStamp === null) {
            return $stack->next()->handle($envelope, $stack);
        }

        $parentContext = Globals::propagator()->extract(
            $traceStamp->carrier,
        );

        $message = $envelope->getMessage();

        $tracer = Globals::tracerProvider()
            ->getTracer('grailjob.messenger');

        $span = $tracer
            ->spanBuilder(
                'messenger.consume '.$message::class,
            )
            ->setParent($parentContext)
            ->setSpanKind(SpanKind::KIND_CONSUMER)
            ->startSpan();

        $scope = $span->activate();

        try {
            return $stack->next()->handle($envelope, $stack);
        } catch (\Throwable $exception) {
            $span->recordException($exception);

            throw $exception;
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
