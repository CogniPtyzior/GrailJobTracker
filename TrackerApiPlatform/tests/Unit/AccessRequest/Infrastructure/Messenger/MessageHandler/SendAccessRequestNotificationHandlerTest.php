<?php

declare(strict_types=1);

/*
 * Unit tests for the access request notification message handler.
 * They cover worker behavior without relying on real Messenger transports or SMTP.
 */

use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\AccessRequest\Infrastructure\Messenger\MessageHandler\SendAccessRequestNotificationHandler;
use App\AccessRequest\Infrastructure\Notification\AccessRequestNotificationSender;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;

it('sends a notification for an existing access request', function (): void {
    $accessRequest = notificationHandlerAccessRequest();
    $mailer = notificationHandlerMailer();
    $handler = notificationHandler(new InMemoryAccessRequestRepository([$accessRequest]), $mailer);

    $handler(new SendAccessRequestNotification($accessRequest->getId()->toRfc4122()));

    expect($mailer->messages)->toHaveCount(1);
});

it('skips missing access requests without sending email', function (): void {
    $mailer = notificationHandlerMailer();
    $handler = notificationHandler(new InMemoryAccessRequestRepository(), $mailer);

    $handler(new SendAccessRequestNotification('018f6d6f-0000-7000-8000-000000000901'));

    expect($mailer->messages)->toBe([]);
});

it('skips malformed access request ids without sending email', function (): void {
    $mailer = notificationHandlerMailer();
    $handler = notificationHandler(new InMemoryAccessRequestRepository(), $mailer);

    $handler(new SendAccessRequestNotification('not-a-valid-uuid'));

    expect($mailer->messages)->toBe([]);
});

function notificationHandler(
    InMemoryAccessRequestRepository $repository,
    MailerInterface $mailer,
): SendAccessRequestNotificationHandler {
    $sender = new AccessRequestNotificationSender(
        $mailer,
        new NullLogger(),
        'admin@example.local',
        'no-reply@example.local',
        'GrailJob Tracker',
    );

    return new SendAccessRequestNotificationHandler($repository, $sender, new NullLogger());
}

function notificationHandlerAccessRequest(): AccessRequest
{
    return new AccessRequest(
        EmailAddress::fromString('p20-handler@example.com'),
        AccessRequestCompanyName::fromString('Acme'),
        AccessRequestReason::fromString('This request should be used by the notification handler test.'),
    );
}

function notificationHandlerMailer(): MailerInterface
{
    return new class implements MailerInterface {
        /** @var list<RawMessage> */
        public array $messages = [];

        public function send(RawMessage $message, ?Envelope $envelope = null): void
        {
            $this->messages[] = $message;
        }
    };
}
