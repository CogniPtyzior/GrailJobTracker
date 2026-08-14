<?php

declare(strict_types=1);

/*
 * Mapper from tracked job domain/application results to API output DTOs.
 * It replaces legacy presenter behavior with an API Platform-specific adapter.
 */

namespace App\TrackedJob\Api\Mapper;

use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Output\TrackedJobOutput;
use App\TrackedJob\Application\Result\SearchTrackedJobsResult;
use App\TrackedJob\Domain\Entity\TrackedJob;
use DateTimeInterface;

final readonly class TrackedJobApiMapper
{
    public function toItemOutput(TrackedJob $trackedJob): TrackedJobItemOutput
    {
        return new TrackedJobItemOutput($this->toOutput($trackedJob));
    }

    public function toCollectionOutput(SearchTrackedJobsResult $result, int $page, int $pageSize): TrackedJobCollectionOutput
    {
        return new TrackedJobCollectionOutput(
            items: array_map($this->toOutput(...), $result->items),
            page: $page,
            pageSize: $pageSize,
            hasMore: $result->hasMore,
        );
    }

    public function toOutput(TrackedJob $trackedJob): TrackedJobOutput
    {
        $timeline = $trackedJob->timeline();

        return new TrackedJobOutput(
            id: $trackedJob->getId()->toRfc4122(),
            company: $trackedJob->company()?->value(),
            title: $trackedJob->title()?->value(),
            contractType: $trackedJob->getContractType()->value,
            location: $trackedJob->getLocation(),
            remoteMode: $trackedJob->getRemoteMode()?->value,
            remuneration: $trackedJob->getRemuneration(),
            offerUrl: $trackedJob->offerUrl()?->value(),
            notes: $trackedJob->notes()?->value(),
            applicationDate: $timeline->applicationDate()?->format(DateTimeInterface::ATOM),
            plannedFollowUpDate: $timeline->plannedFollowUpDate()?->format(DateTimeInterface::ATOM),
            effectiveFollowUpDate: $timeline->effectiveFollowUpDate()?->format(DateTimeInterface::ATOM),
            firstContactDate: $timeline->firstContactDate()?->format(DateTimeInterface::ATOM),
            preliminaryInterviewDate: $timeline->preliminaryInterviewDate()?->format(DateTimeInterface::ATOM),
            secondInterviewDate: $timeline->secondInterviewDate()?->format(DateTimeInterface::ATOM),
            hrContactName: $trackedJob->hrContactName()?->value(),
            businessContactName: $trackedJob->businessContactName()?->value(),
            subjectiveRelevance: $trackedJob->getSubjectiveRelevance(),
            status: $trackedJob->getStatus()->value,
            createdAt: $trackedJob->getCreatedAt()->format(DateTimeInterface::ATOM),
            updatedAt: $trackedJob->getUpdatedAt()->format(DateTimeInterface::ATOM),
        );
    }
}
