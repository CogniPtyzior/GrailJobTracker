<?php

namespace App\AccessRequest\Application;

use App\AccessRequest\Domain\Entity\AccessRequest;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

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
        $email = (new Email())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to($this->recipientEmail)
            ->subject('Nouvelle demande d\'accès GrailJob Tracker')
            ->text($this->buildMessage($accessRequest));

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $exception) {
            $this->logger->error('Failed to send access request notification email.', [
                'exception' => $exception,
                'accessRequestId' => $accessRequest->getId()->toRfc4122(),
                'recipientEmail' => $this->recipientEmail,
            ]);
        }
    }

    private function buildMessage(AccessRequest $accessRequest): string
    {
        return sprintf(
            "Une nouvelle demande d'accès a été créée.\n\nEmail: %s\nEntreprise: %s\nPrénom: %s\nNom: %s\nCréée le: %s\n\nMotif:\n%s\n",
            $accessRequest->getEmail(),
            $accessRequest->getCompanyName(),
            $accessRequest->getFirstName() ?? '',
            $accessRequest->getLastName() ?? '',
            $accessRequest->getCreatedAt()->format('d/m/Y H:i:s'),
            $accessRequest->getReason(),
        );
    }
}