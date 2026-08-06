<?php

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
use App\TrackedJob\Infrastructure\Doctrine\Entity\TrackedJobRecord;

final class TrackedJobRecordMapper
{
    public function toDomain(TrackedJobRecord $record): TrackedJob
    {
        $subjectiveRelevance = $record->getSubjectiveRelevance();

        return TrackedJob::reconstitute(
            TrackedJobId::fromUuid($record->getId()),
            UserId::fromUuid($record->getOwner()->getId()),
            CompanyName::fromNullable($record->getCompany()),
            JobTitle::fromNullable($record->getTitle()),
            $record->getContractType(),
            $record->getLocation(),
            $record->getRemoteMode(),
            $record->getRemuneration(),
            OfferUrl::fromNullable($record->getOfferUrl()),
            TrackedJobNotes::fromNullable($record->getNotes()),
            $record->getApplicationDate(),
            $record->getPlannedFollowUpDate(),
            $record->getEffectiveFollowUpDate(),
            $record->getFirstContactDate(),
            $record->getPreliminaryInterviewDate(),
            $record->getSecondInterviewDate(),
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

        $record->setId($trackedJob->getId()->toUuid());
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
