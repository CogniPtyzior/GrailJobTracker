<?php

namespace App\TrackedJob\Infrastructure\Doctrine\Entity;

use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'tracked_jobs', schema: 'trackers')]
#[ORM\Index(columns: ['status'], name: 'idx_tracked_jobs_status')]
#[ORM\Index(columns: ['contract_type'], name: 'idx_tracked_jobs_contract_type')]
#[ORM\Index(columns: ['remote_mode'], name: 'idx_tracked_jobs_remote_mode')]
#[ORM\Index(columns: ['application_date'], name: 'idx_tracked_jobs_application_date')]
#[ORM\Index(columns: ['planned_follow_up_date'], name: 'idx_tracked_jobs_followup_date')]
#[ORM\Index(columns: ['subjective_relevance'], name: 'idx_tracked_jobs_relevance')]
#[ORM\Index(columns: ['owner_id'], name: 'idx_tracked_jobs_owner')]
final class TrackedJobRecord
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: UserRecord::class)]
    #[ORM\JoinColumn(name: 'owner_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private UserRecord $owner;

    #[ORM\Column(length: 255, nullable: true)] private ?string $company = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $title = null;
    #[ORM\Column(name: 'contract_type', enumType: ContractType::class, nullable: true)] private ?ContractType $contractType = ContractType::CDI;
    #[ORM\Column(length: 255, nullable: true)] private ?string $location = null;
    #[ORM\Column(name: 'remote_mode', enumType: RemoteMode::class, nullable: true)] private ?RemoteMode $remoteMode = null;
    #[ORM\Column(length: 255, nullable: true)] private ?string $remuneration = null;
    #[ORM\Column(name: 'offer_url', type: 'text', nullable: true)] private ?string $offerUrl = null;
    #[ORM\Column(type: 'text', nullable: true)] private ?string $notes = null;
    #[ORM\Column(name: 'application_date', nullable: true)] private ?\DateTimeImmutable $applicationDate = null;
    #[ORM\Column(name: 'planned_follow_up_date', nullable: true)] private ?\DateTimeImmutable $plannedFollowUpDate = null;
    #[ORM\Column(name: 'effective_follow_up_date', nullable: true)] private ?\DateTimeImmutable $effectiveFollowUpDate = null;
    #[ORM\Column(name: 'first_contact_date', nullable: true)] private ?\DateTimeImmutable $firstContactDate = null;
    #[ORM\Column(name: 'preliminary_interview_date', nullable: true)] private ?\DateTimeImmutable $preliminaryInterviewDate = null;
    #[ORM\Column(name: 'second_interview_date', nullable: true)] private ?\DateTimeImmutable $secondInterviewDate = null;
    #[ORM\Column(name: 'hr_contact_name', length: 255, nullable: true)] private ?string $hrContactName = null;
    #[ORM\Column(name: 'business_contact_name', length: 255, nullable: true)] private ?string $businessContactName = null;
    #[ORM\Column(name: 'subjective_relevance', nullable: true)] private ?int $subjectiveRelevance = null;
    #[ORM\Column(enumType: TrackedJobStatus::class)] private TrackedJobStatus $status = TrackedJobStatus::DRAFT;
    #[ORM\Column(name: 'created_at')] private \DateTimeImmutable $createdAt;
    #[ORM\Column(name: 'updated_at')] private \DateTimeImmutable $updatedAt;

    public function getId(): Uuid { return $this->id; }
    public function setId(Uuid $id): void { $this->id = $id; }
    public function getOwner(): UserRecord { return $this->owner; }
    public function setOwner(UserRecord $owner): void { $this->owner = $owner; }
    public function getCompany(): ?string { return $this->company; }
    public function setCompany(?string $company): void { $this->company = $company; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): void { $this->title = $title; }
    public function getContractType(): ?ContractType { return $this->contractType; }
    public function setContractType(?ContractType $contractType): void { $this->contractType = $contractType; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $location): void { $this->location = $location; }
    public function getRemoteMode(): ?RemoteMode { return $this->remoteMode; }
    public function setRemoteMode(?RemoteMode $remoteMode): void { $this->remoteMode = $remoteMode; }
    public function getRemuneration(): ?string { return $this->remuneration; }
    public function setRemuneration(?string $remuneration): void { $this->remuneration = $remuneration; }
    public function getOfferUrl(): ?string { return $this->offerUrl; }
    public function setOfferUrl(?string $offerUrl): void { $this->offerUrl = $offerUrl; }
    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): void { $this->notes = $notes; }
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
    public function setHrContactName(?string $hrContactName): void { $this->hrContactName = $hrContactName; }
    public function getBusinessContactName(): ?string { return $this->businessContactName; }
    public function setBusinessContactName(?string $businessContactName): void { $this->businessContactName = $businessContactName; }
    public function getSubjectiveRelevance(): ?int { return $this->subjectiveRelevance; }
    public function setSubjectiveRelevance(?int $subjectiveRelevance): void { $this->subjectiveRelevance = $subjectiveRelevance; }
    public function getStatus(): TrackedJobStatus { return $this->status; }
    public function setStatus(TrackedJobStatus $status): void { $this->status = $status; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): void { $this->createdAt = $createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void { $this->updatedAt = $updatedAt; }
}