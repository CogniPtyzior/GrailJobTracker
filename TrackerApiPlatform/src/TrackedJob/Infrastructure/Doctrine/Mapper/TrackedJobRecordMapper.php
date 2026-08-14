<?php

declare(strict_types=1);

/*
 * Mapper between the tracked job aggregate and the Doctrine tracked job record.
 * It is the explicit boundary between the domain model and the preserved database schema.
 */

namespace App\TrackedJob\Infrastructure\Doctrine\Mapper;

use App\Security\Domain\ValueObject\UserId;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;
use App\TrackedJob\Infrastructure\Doctrine\Entity\TrackedJobRecord;
use Symfony\Component\Uid\Uuid;

final readonly class TrackedJobRecordMapper
{
    public function toDomain(TrackedJobRecord $record): TrackedJob
    {
        $subjectiveRelevance = $record->getSubjectiveRelevance();

        return TrackedJob::reconstitute(
            TrackedJobId::fromString($record->getId()->toRfc4122()),
            UserId::fromString($record->getOwner()->getId()->toRfc4122()),
            CompanyName::fromNullable($record->getCompany()),
            JobTitle::fromNullable($record->getTitle()),
            $record->getContractType(),
            $record->getLocation(),
            $record->getRemoteMode(),
            $record->getRemuneration(),
            OfferUrl::fromNullable($record->getOfferUrl()),
            TrackedJobNotes::fromNullable($record->getNotes()),
            TrackedJobTimeline::fromPersistedState(
                $record->getApplicationDate(),
                $record->getPlannedFollowUpDate(),
                $record->getEffectiveFollowUpDate(),
                $record->getFirstContactDate(),
                $record->getPreliminaryInterviewDate(),
                $record->getSecondInterviewDate(),
            ),
            ContactName::fromNullable($record->getHrContactName()),
            ContactName::fromNullable($record->getBusinessContactName()),
            $subjectiveRelevance !== null ? SubjectiveRelevance::fromInt($subjectiveRelevance) : null,
            $record->getStatus(),
            $record->getCreatedAt(),
            $record->getUpdatedAt(),
        );
    }

    public function updateRecord(TrackedJob $trackedJob, TrackedJobRecord $record, UserRecord $ownerRecord): void
    {
        $timeline = $trackedJob->timeline();

        $record->setId(Uuid::fromString($trackedJob->getId()->toRfc4122()));
        $record->setOwner($ownerRecord);
        $record->setCompany($trackedJob->company()?->value());
        $record->setTitle($trackedJob->title()?->value());
        $record->setContractType($trackedJob->getContractType());
        $record->setLocation($trackedJob->getLocation());
        $record->setRemoteMode($trackedJob->getRemoteMode());
        $record->setRemuneration($trackedJob->getRemuneration());
        $record->setOfferUrl($trackedJob->offerUrl()?->value());
        $record->setNotes($trackedJob->notes()?->value());
        $record->setApplicationDate($timeline->applicationDate());
        $record->setPlannedFollowUpDate($timeline->plannedFollowUpDate());
        $record->setEffectiveFollowUpDate($timeline->effectiveFollowUpDate());
        $record->setFirstContactDate($timeline->firstContactDate());
        $record->setPreliminaryInterviewDate($timeline->preliminaryInterviewDate());
        $record->setSecondInterviewDate($timeline->secondInterviewDate());
        $record->setHrContactName($trackedJob->hrContactName()?->value());
        $record->setBusinessContactName($trackedJob->businessContactName()?->value());
        $record->setSubjectiveRelevance($trackedJob->subjectiveRelevance()?->value());
        $record->setStatus($trackedJob->getStatus());
        $record->setCreatedAt($trackedJob->getCreatedAt());
        $record->setUpdatedAt($trackedJob->getUpdatedAt());
    }
}
