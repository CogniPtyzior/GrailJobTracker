<?php

namespace App\Tests\Unit\AccessRequest\Application\MessageHandler;

use App\AccessRequest\Application\Notification\AccessRequestNotificationSender;
use App\AccessRequest\Application\Message\SendAccessRequestNotification;
use App\AccessRequest\Application\MessageHandler\SendAccessRequestNotificationHandler;
use App\Tests\Support\Builder\AccessRequestBuilder;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Uid\UuidV7;

/**
 * Covers the worker-side behavior independently from Messenger transport details.
 *
 * These tests keep both the repository and mailer in memory so they focus on the handler contract:
 * reload the access request by id, send when it still exists, and acknowledge messages that cannot
 * produce a valid aggregate anymore.
 */
final class SendAccessRequestNotificationHandlerTest extends TestCase
{
    public function testHandlerSendsNotificationForExistingAccessRequest(): void
    {
        $accessRequest = AccessRequestBuilder::anAccessRequest()->build();
        $mailer = new SpyMailer();
        $handler = $this->handler(new InMemoryAccessRequestRepository([$accessRequest]), $mailer);

        // A persisted request id is enough for the worker to rebuild context and send the email.
        $handler(new SendAccessRequestNotification($accessRequest->getId()->toRfc4122()));

        self::assertSame(1, $mailer->sentCount());
    }

    public function testHandlerSkipsMissingAccessRequest(): void
    {
        $mailer = new SpyMailer();
        $handler = $this->handler(new InMemoryAccessRequestRepository(), $mailer);

        // Stale messages are acknowledged without sending to avoid retrying deleted requests forever.
        $handler(new SendAccessRequestNotification((new UuidV7())->toRfc4122()));

        self::assertSame(0, $mailer->sentCount());
    }

    public function testHandlerSkipsInvalidAccessRequestId(): void
    {
        $mailer = new SpyMailer();
        $handler = $this->handler(new InMemoryAccessRequestRepository(), $mailer);

        // Malformed messages are discarded because no valid aggregate can be loaded from them.
        $handler(new SendAccessRequestNotification('not-a-valid-uuid'));

        self::assertSame(0, $mailer->sentCount());
    }

    private function handler(
        InMemoryAccessRequestRepository $repository,
        SpyMailer $mailer,
    ): SendAccessRequestNotificationHandler {
        $sender = new AccessRequestNotificationSender(
            $mailer,
            new NullLogger(),
            'admin@example.local',
            'no-reply@example.local',
            'GrailJob',
        );

        return new SendAccessRequestNotificationHandler($repository, $sender, new NullLogger());
    }
}

/**
 * Minimal mailer spy used to assert that the handler delegates email sending when appropriate.
 */
final class SpyMailer implements MailerInterface
{
    /** @var list<RawMessage> */
    private array $messages = [];

    public function send(RawMessage $message, ?Envelope $envelope = null): void
    {
        $this->messages[] = $message;
    }

    public function sentCount(): int
    {
        return count($this->messages);
    }
}
