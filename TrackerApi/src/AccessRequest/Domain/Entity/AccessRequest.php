<?php

namespace App\AccessRequest\Domain\Entity;

use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

/**
 * Domain entity that represents and normalizes a public access request.
 */
final class AccessRequest
{
    private Uuid $id;
    private string $email;
    private string $normalizedEmail;
    private string $companyName;
    private string $reason;
    private ?string $firstName = null;
    private ?string $lastName = null;
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email, string $normalizedEmail, string $companyName, string $reason)
    {
        $this->id = new UuidV7();
        $this->email = $email;
        $this->normalizedEmail = $normalizedEmail;
        $this->companyName = trim($companyName);
        $this->reason = trim($reason);
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public static function submit(
        string $email,
        string $normalizedEmail,
        string $companyName,
        string $reason,
        ?string $firstName,
        ?string $lastName,
    ): self {
        $accessRequest = new self($email, $normalizedEmail, $companyName, $reason);
        $accessRequest->updateRequesterName($firstName, $lastName);

        return $accessRequest;
    }

    public static function reconstitute(
        Uuid $id,
        string $email,
        string $normalizedEmail,
        string $companyName,
        string $reason,
        ?string $firstName,
        ?string $lastName,
        \DateTimeImmutable $createdAt,
    ): self {
        $accessRequest = new self($email, $normalizedEmail, $companyName, $reason);
        $accessRequest->id = $id;
        $accessRequest->firstName = $accessRequest->trimOrNull($firstName);
        $accessRequest->lastName = $accessRequest->trimOrNull($lastName);
        $accessRequest->createdAt = $createdAt;

        return $accessRequest;
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getNormalizedEmail(): string
    {
        return $this->normalizedEmail;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateRequesterName(?string $firstName, ?string $lastName): void
    {
        $this->firstName = $this->trimOrNull($firstName);
        $this->lastName = $this->trimOrNull($lastName);
    }

    private function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}