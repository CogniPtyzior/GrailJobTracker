<?php

namespace App\AccessRequest\Domain\Entity;

use App\AccessRequest\Infrastructure\Doctrine\AccessRequestRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: AccessRequestRepository::class)]
#[ORM\Table(name: 'access_requests', schema: 'trackers')]
#[ORM\Index(columns: ['normalized_email'], name: 'idx_access_requests_normalized_email')]
#[ORM\Index(columns: ['company_name'], name: 'idx_access_requests_company_name')]
#[ORM\Index(columns: ['created_at'], name: 'idx_access_requests_created_at')]
final class AccessRequest
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(name: 'normalized_email', length: 180)]
    private string $normalizedEmail;

    #[ORM\Column(length: 255)]
    private string $companyName;

    #[ORM\Column(type: 'text')]
    private string $reason;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email, string $normalizedEmail, string $companyName, string $reason)
    {
        $this->id = new UuidV7();
        $this->email = $email;
        $this->normalizedEmail = $normalizedEmail;
        $this->companyName = $companyName;
        $this->reason = $reason;
        $this->createdAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): Uuid { return $this->id; }
    public function getEmail(): string { return $this->email; }
    public function getNormalizedEmail(): string { return $this->normalizedEmail; }
    public function getCompanyName(): string { return $this->companyName; }
    public function getReason(): string { return $this->reason; }
    public function getFirstName(): ?string { return $this->firstName; }
    public function getLastName(): ?string { return $this->lastName; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function setFirstName(?string $firstName): void
    {
        $this->firstName = $this->trimOrNull($firstName);
    }

    public function setLastName(?string $lastName): void
    {
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
