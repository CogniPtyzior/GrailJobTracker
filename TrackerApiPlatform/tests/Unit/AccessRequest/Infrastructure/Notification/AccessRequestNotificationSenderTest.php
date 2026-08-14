<?php

declare(strict_types=1);

/*
 * Unit tests for the access request notification sender.
 * They verify the generated email envelope and body without using a real mail transport.
 */

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\AccessRequest\Infrastructure\Notification\AccessRequestNotificationSender;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

it('builds and sends the access request notification email', function (): void {
    $mailer = notificationSenderMailer();
    $accessRequest = AccessRequest::submit(
        EmailAddress::fromString('Applicant@example.com'),
        AccessRequestCompanyName::fromString('Acme'),
        AccessRequestReason::fromString('This request should produce a notification email body.'),
        PersonName::fromNullable('Jane'),
        PersonName::fromNullable('Doe'),
    );
    $sender = new AccessRequestNotificationSender(
        $mailer,
        new NullLogger(),
        'admin@example.local',
        'no-reply@example.local',
        'GrailJob Tracker',
    );

    $sender->sendCreatedNotificationOrFail($accessRequest);

    expect($mailer->messages)->toHaveCount(1)
        ->and($mailer->messages[0])->toBeInstanceOf(Email::class)
        ->and($mailer->messages[0]->getSubject())->toBe('Nouvelle demande d\'accès GrailJob Tracker')
        ->and($mailer->messages[0]->getTo()[0]->getAddress())->toBe('admin@example.local')
        ->and($mailer->messages[0]->getTextBody())->toContain('Applicant@example.com')
        ->and($mailer->messages[0]->getTextBody())->toContain('Acme')
        ->and($mailer->messages[0]->getTextBody())->toContain('Jane')
        ->and($mailer->messages[0]->getTextBody())->toContain('Doe');
});

function notificationSenderMailer(): MailerInterface
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
