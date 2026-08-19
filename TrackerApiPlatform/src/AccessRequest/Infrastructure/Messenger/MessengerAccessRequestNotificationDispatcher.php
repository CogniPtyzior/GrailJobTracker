<?php

declare(strict_types=1);

/*
 * Messenger adapter for the access request notification dispatcher port.
 * The application triggers the port while this infrastructure adapter decides how the async message is dispatched.
 */

namespace App\AccessRequest\Infrastructure\Messenger;

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Application\Notification\AccessRequestNotificationDispatcherInterface;
use App\AccessRequest\Domain\Entity\AccessRequest;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Shared\Infrastructure\Messenger\Stamp\OpenTelemetryTraceStamp;
use OpenTelemetry\API\Globals;
use Symfony\Component\Messenger\Envelope;
use OpenTelemetry\API\Trace\SpanKind;

final readonly class MessengerAccessRequestNotificationDispatcher implements AccessRequestNotificationDispatcherInterface
{
    public function __construct(private MessageBusInterface $messageBus)
    {
    }

    public function dispatchCreated(AccessRequest $accessRequest): void
    {
         $tracer = Globals::tracerProvider()
            ->getTracer('grailjob.messenger');

        $span = $tracer
            ->spanBuilder('messenger.publish SendAccessRequestNotification')
            ->setSpanKind(SpanKind::KIND_PRODUCER)
            ->startSpan();

        $scope = $span->activate();

        try {
            $carrier = [];

            Globals::propagator()->inject($carrier);

            $this->messageBus->dispatch(
                new Envelope(
                    new SendAccessRequestNotification(
                        $accessRequest->getId()->toRfc4122(),
                    ),
                    [
                        new OpenTelemetryTraceStamp($carrier),
                    ],
                ),
            );
        } finally {
            $scope->detach();
            $span->end();
        }
    }
}
