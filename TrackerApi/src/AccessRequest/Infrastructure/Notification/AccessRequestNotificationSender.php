<?php

namespace App\AccessRequest\Infrastructure\Notification;

use App\AccessRequest\Domain\Entity\AccessRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * Builds and sends the access request notification email.
 */
final class AccessRequestNotificationSender
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly string $recipientEmail,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
    }

    public function sendCreatedNotification(AccessRequest $accessRequest): void
    {
        try {
            $this->sendCreatedNotificationOrFail($accessRequest);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to send access request notification email.', [
                'exception' => $exception,
                'accessRequestId' => $accessRequest->getId()->toRfc4122(),
                'recipientEmail' => $this->recipientEmail,
            ]);
        }
    }

    public function sendCreatedNotificationOrFail(AccessRequest $accessRequest): void
    {
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($this->recipientEmail)
            ->subject('Nouvelle demande d\'accès GrailJob Tracker')
            ->text($this->buildMessage($accessRequest));

        $this->mailer->send($email);
    }

    private function buildMessage(AccessRequest $accessRequest): string
    {
        return sprintf(
            "Une nouvelle demande d'accès a été créée.\n\nEmail: %s\nEntreprise: %s\nPrénom: %s\nNom: %s\nCréée le: %s\n\nMotif:\n%s\n",
            $accessRequest->getEmail(),
            $accessRequest->getCompanyName(),
            $accessRequest->firstName()?->value() ?? '',
            $accessRequest->lastName()?->value() ?? '',
            $accessRequest->getCreatedAt()->format('d/m/Y H:i:s'),
            $accessRequest->reason()->value(),
        );
    }
}

