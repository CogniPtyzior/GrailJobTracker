<?php

declare(strict_types=1);

/*
 * Mailer adapter building and sending access request notification emails.
 * It is intentionally infrastructure-only: application code dispatches notification intent through a port.
 */

namespace App\AccessRequest\Infrastructure\Notification;

use App\AccessRequest\Domain\Entity\AccessRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class AccessRequestNotificationSender
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $recipientEmail,
        private string $fromEmail,
        private string $fromName,
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
