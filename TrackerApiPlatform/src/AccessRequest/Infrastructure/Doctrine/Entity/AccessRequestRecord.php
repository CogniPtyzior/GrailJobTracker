<?php

declare(strict_types=1);

/*
 * Doctrine record mapped to the existing trackers.access_requests table.
 * It is a persistence model only and must never become an API Platform resource or domain aggregate.
 */

namespace App\AccessRequest\Infrastructure\Doctrine\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'access_requests', schema: 'trackers')]
#[ORM\Index(columns: ['normalized_email'], name: 'idx_access_requests_normalized_email')]
#[ORM\Index(columns: ['company_name'], name: 'idx_access_requests_company_name')]
#[ORM\Index(columns: ['created_at'], name: 'idx_access_requests_created_at')]
final class AccessRequestRecord
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(name: 'normalized_email', length: 180)]
    private string $normalizedEmail;

    #[ORM\Column(name: 'company_name', length: 255)]
    private string $companyName;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(name: 'first_name', length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(name: 'last_name', length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function setId(Uuid $id): void
    {
        $this->id = $id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getNormalizedEmail(): string
    {
        return $this->normalizedEmail;
    }

    public function setNormalizedEmail(string $normalizedEmail): void
    {
        $this->normalizedEmail = $normalizedEmail;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): void
    {
        $this->companyName = $companyName;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function setReason(string $reason): void
    {
        $this->reason = $reason;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): void
    {
        $this->lastName = $lastName;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
}
