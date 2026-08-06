<?php

namespace App\TrackedJob\Infrastructure\Doctrine\Mapper;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Infrastructure\Doctrine\Entity\TrackedJobRecord;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\Security\Infrastructure\Doctrine\Mapper\UserRecordMapper;

final class TrackedJobRecordMapper
{
    public function __construct(private readonly UserRecordMapper $userMapper)
    {
    }

    public function toDomain(TrackedJobRecord $record): TrackedJob
    {
        return TrackedJob::reconstitute(
            $record->getId(),
            $this->userMapper->toDomain($record->getOwner()),
            $record->getCompany(),
            $record->getTitle(),
            $record->getContractType(),
            $record->getLocation(),
            $record->getRemoteMode(),
            $record->getRemuneration(),
            $record->getOfferUrl(),
            $record->getNotes(),
            $record->getApplicationDate(),
            $record->getPlannedFollowUpDate(),
            $record->getEffectiveFollowUpDate(),
            $record->getFirstContactDate(),
            $record->getPreliminaryInterviewDate(),
            $record->getSecondInterviewDate(),
            $record->getHrContactName(),
            $record->getBusinessContactName(),
            $record->getSubjectiveRelevance(),
            $record->getStatus(),
            $record->getCreatedAt(),
            $record->getUpdatedAt(),
        );
    }

    public function updateRecord(TrackedJob $trackedJob, TrackedJobRecord $record, UserRecord $ownerRecord): void
    {
        $record->setId($trackedJob->getId());
        $record->setOwner($ownerRecord);
        $record->setCompany($trackedJob->getCompany());
        $record->setTitle($trackedJob->getTitle());
        $record->setContractType($trackedJob->getContractType());
        $record->setLocation($trackedJob->getLocation());
        $record->setRemoteMode($trackedJob->getRemoteMode());
        $record->setRemuneration($trackedJob->getRemuneration());
        $record->setOfferUrl($trackedJob->getOfferUrl());
        $record->setNotes($trackedJob->getNotes());
        $record->setApplicationDate($trackedJob->getApplicationDate());
        $record->setPlannedFollowUpDate($trackedJob->getPlannedFollowUpDate());
        $record->setEffectiveFollowUpDate($trackedJob->getEffectiveFollowUpDate());
        $record->setFirstContactDate($trackedJob->getFirstContactDate());
        $record->setPreliminaryInterviewDate($trackedJob->getPreliminaryInterviewDate());
        $record->setSecondInterviewDate($trackedJob->getSecondInterviewDate());
        $record->setHrContactName($trackedJob->getHrContactName());
        $record->setBusinessContactName($trackedJob->getBusinessContactName());
        $record->setSubjectiveRelevance($trackedJob->getSubjectiveRelevance());
        $record->setStatus($trackedJob->getStatus());
        $record->setCreatedAt($trackedJob->getCreatedAt());
        $record->setUpdatedAt($trackedJob->getUpdatedAt());
    }
}