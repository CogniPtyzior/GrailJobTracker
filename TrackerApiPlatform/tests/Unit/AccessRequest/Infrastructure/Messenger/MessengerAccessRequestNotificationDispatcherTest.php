<?php

declare(strict_types=1);

/*
 * Unit tests for the Messenger access request notification dispatcher.
 * They verify that the application notification port emits a stable id message instead of serializing aggregates.
 */

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\AccessRequest\Infrastructure\Messenger\MessengerAccessRequestNotificationDispatcher;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

it('dispatches an async notification message carrying only the access request id', function (): void {
    $messageBus = new class implements MessageBusInterface {
        public ?object $message = null;

        public function dispatch(object $message, array $stamps = []): Envelope
        {
            $this->message = $message;

            return new Envelope($message, $stamps);
        }
    };
    $accessRequest = new AccessRequest(
        EmailAddress::fromString('p20-dispatcher@example.com'),
        AccessRequestCompanyName::fromString('Acme'),
        AccessRequestReason::fromString('This request should dispatch an async notification message.'),
    );

    (new MessengerAccessRequestNotificationDispatcher($messageBus))->dispatchCreated($accessRequest);

    expect($messageBus->message)->toBeInstanceOf(SendAccessRequestNotification::class)
        ->and($messageBus->message->accessRequestId)->toBe($accessRequest->getId()->toRfc4122());
});
