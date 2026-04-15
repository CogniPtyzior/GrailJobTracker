<?php

namespace App\TrackedJob\Domain\Entity;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Infrastructure\Doctrine\TrackedJobRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: TrackedJobRepository::class)]
#[ORM\Table(name: 'tracked_jobs', schema: 'trackers')]
#[ORM\Index(columns: ['status'], name: 'idx_tracked_jobs_status')]
#[ORM\Index(columns: ['contract_type'], name: 'idx_tracked_jobs_contract_type')]
#[ORM\Index(columns: ['remote_mode'], name: 'idx_tracked_jobs_remote_mode')]
#[ORM\Index(columns: ['application_date'], name: 'idx_tracked_jobs_application_date')]
#[ORM\Index(columns: ['planned_follow_up_date'], name: 'idx_tracked_jobs_followup_date')]
#[ORM\Index(columns: ['subjective_relevance'], name: 'idx_tracked_jobs_relevance')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_tracked_jobs_owner')]
final class TrackedJob
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $owner;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $company = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(enumType: ContractType::class, nullable: true)]
    private ?ContractType $contractType = ContractType::CDI;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(enumType: RemoteMode::class, nullable: true)]
    private ?RemoteMode $remoteMode = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $remuneration = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $offerUrl = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $applicationDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $plannedFollowUpDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $effectiveFollowUpDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $firstContactDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $preliminaryInterviewDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $secondInterviewDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $hrContactName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $businessContactName = null;

    #[ORM\Column(nullable: true)]
    private ?int $subjectiveRelevance = null;

    #[ORM\Column(enumType: TrackedJobStatus::class)]
    private TrackedJobStatus $status = TrackedJobStatus::DRAFT;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(User $owner)
    {
        $this->id = new UuidV7();
        $this->owner = $owner;
        $now = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function getId(): Uuid { return $this->id; }
    public function getOwner(): User { return $this->owner; }
    public function getCompany(): ?string { return $this->company; }
    public function setCompany(?string $company): void { $this->company = self::trimOrNull($company); }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): void { $this->title = self::trimOrNull($title); }
    public function getContractType(): ?ContractType { return $this->contractType; }
    public function setContractType(?ContractType $contractType): void { $this->contractType = $contractType; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): void { $this->location = self::trimOrNull($location); }
    public function getRemoteMode(): ?RemoteMode { return $this->remoteMode; }
    public function setRemoteMode(?RemoteMode $remoteMode): void { $this->remoteMode = $remoteMode; }
    public function getRemuneration(): ?string { return $this->remuneration; }
    public function setRemuneration(?string $remuneration): void { $this->remuneration = self::trimOrNull($remuneration); }
    public function getOfferUrl(): ?string { return $this->offerUrl; }
    public function setOfferUrl(?string $offerUrl): void { $this->offerUrl = self::trimOrNull($offerUrl); }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): void { $this->notes = self::trimOrNull($notes); }
    public function getApplicationDate(): ?\DateTimeImmutable { return $this->applicationDate; }
    public function setApplicationDate(?\DateTimeImmutable $applicationDate): void { $this->applicationDate = $applicationDate; }
    public function getPlannedFollowUpDate(): ?\DateTimeImmutable { return $this->plannedFollowUpDate; }
    public function setPlannedFollowUpDate(?\DateTimeImmutable $plannedFollowUpDate): void { $this->plannedFollowUpDate = $plannedFollowUpDate; }
    public function getEffectiveFollowUpDate(): ?\DateTimeImmutable { return $this->effectiveFollowUpDate; }
    public function setEffectiveFollowUpDate(?\DateTimeImmutable $effectiveFollowUpDate): void { $this->effectiveFollowUpDate = $effectiveFollowUpDate; }
    public function getFirstContactDate(): ?\DateTimeImmutable { return $this->firstContactDate; }
    public function setFirstContactDate(?\DateTimeImmutable $firstContactDate): void { $this->firstContactDate = $firstContactDate; }
    public function getPreliminaryInterviewDate(): ?\DateTimeImmutable { return $this->preliminaryInterviewDate; }
    public function setPreliminaryInterviewDate(?\DateTimeImmutable $preliminaryInterviewDate): void { $this->preliminaryInterviewDate = $preliminaryInterviewDate; }
    public function getSecondInterviewDate(): ?\DateTimeImmutable { return $this->secondInterviewDate; }
    public function setSecondInterviewDate(?\DateTimeImmutable $secondInterviewDate): void { $this->secondInterviewDate = $secondInterviewDate; }
    public function getHrContactName(): ?string { return $this->hrContactName; }
    public function setHrContactName(?string $hrContactName): void { $this->hrContactName = self::trimOrNull($hrContactName); }
    public function getBusinessContactName(): ?string { return $this->businessContactName; }
    public function setBusinessContactName(?string $businessContactName): void { $this->businessContactName = self::trimOrNull($businessContactName); }
    public function getSubjectiveRelevance(): ?int { return $this->subjectiveRelevance; }
    public function setSubjectiveRelevance(?int $subjectiveRelevance): void { $this->subjectiveRelevance = $subjectiveRelevance; }
    public function getStatus(): TrackedJobStatus { return $this->status; }
    public function setStatus(TrackedJobStatus $status): void { $this->status = $status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    private static function trimOrNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
