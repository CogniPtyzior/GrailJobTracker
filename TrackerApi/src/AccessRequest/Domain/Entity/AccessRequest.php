<?php

namespace App\AccessRequest\Domain\Entity;

use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;

/**
 * Domain entity that represents and normalizes a public access request.
 */
final class AccessRequest
{
    private AccessRequestId $id;
    private EmailAddress $email;
    private string $companyName;
    private AccessRequestReason $reason;
    private ?PersonName $firstName = null;
    private ?PersonName $lastName = null;
    private \DateTimeImmutable $createdAt;

    public function __construct(EmailAddress $email, string $companyName, AccessRequestReason $reason)
    {
        $this->id = AccessRequestId::new();
        $this->email = $email;
        $this->companyName = trim($companyName);
        $this->reason = $reason;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function submit(
        EmailAddress $email,
        string $companyName,
        AccessRequestReason $reason,
        ?PersonName $firstName,
        ?PersonName $lastName,
    ): self {
        $accessRequest = new self($email, $companyName, $reason);
        $accessRequest->updateRequesterName($firstName, $lastName);

        return $accessRequest;
    }

    public static function reconstitute(
        AccessRequestId $id,
        EmailAddress $email,
        string $companyName,
        AccessRequestReason $reason,
        ?PersonName $firstName,
        ?PersonName $lastName,
        \DateTimeImmutable $createdAt,
    ): self {
        $accessRequest = new self($email, $companyName, $reason);
        $accessRequest->id = $id;
        $accessRequest->firstName = $firstName;
        $accessRequest->lastName = $lastName;
        $accessRequest->createdAt = $createdAt;

        return $accessRequest;
    }

    public function getId(): AccessRequestId
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email->value();
    }

    public function getNormalizedEmail(): string
    {
        return $this->email->normalizedValue();
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function reason(): AccessRequestReason
    {
        return $this->reason;
    }


    public function firstName(): ?PersonName
    {
        return $this->firstName;
    }


    public function lastName(): ?PersonName
    {
        return $this->lastName;
    }


    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateRequesterName(?PersonName $firstName, ?PersonName $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}
