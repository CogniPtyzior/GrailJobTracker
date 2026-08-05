<?php

namespace App\TrackedJob\Presentation;

use App\TrackedJob\Application\Result\SearchTrackedJobsResult;
use App\TrackedJob\Domain\Entity\TrackedJob;

final class TrackedJobPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(TrackedJob $trackedJob): array
    {
        return [
            'id' => $trackedJob->getId()->toRfc4122(),
            'company' => $trackedJob->getCompany(),
            'title' => $trackedJob->getTitle(),
            'contractType' => $trackedJob->getContractType()?->value,
            'location' => $trackedJob->getLocation(),
            'remoteMode' => $trackedJob->getRemoteMode()?->value,
            'remuneration' => $trackedJob->getRemuneration(),
            'offerUrl' => $trackedJob->getOfferUrl(),
            'notes' => $trackedJob->getNotes(),
            'applicationDate' => $trackedJob->getApplicationDate()?->format(\DateTimeInterface::ATOM),
            'plannedFollowUpDate' => $trackedJob->getPlannedFollowUpDate()?->format(\DateTimeInterface::ATOM),
            'effectiveFollowUpDate' => $trackedJob->getEffectiveFollowUpDate()?->format(\DateTimeInterface::ATOM),
            'firstContactDate' => $trackedJob->getFirstContactDate()?->format(\DateTimeInterface::ATOM),
            'preliminaryInterviewDate' => $trackedJob->getPreliminaryInterviewDate()?->format(\DateTimeInterface::ATOM),
            'secondInterviewDate' => $trackedJob->getSecondInterviewDate()?->format(\DateTimeInterface::ATOM),
            'hrContactName' => $trackedJob->getHrContactName(),
            'businessContactName' => $trackedJob->getBusinessContactName(),
            'subjectiveRelevance' => $trackedJob->getSubjectiveRelevance(),
            'status' => $trackedJob->getStatus()->value,
            'createdAt' => $trackedJob->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'updatedAt' => $trackedJob->getUpdatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function presentSearchResult(SearchTrackedJobsResult $result, int $page, int $pageSize): array
    {
        return [
            'items' => array_map($this->present(...), $result->items),
            'page' => $page,
            'pageSize' => $pageSize,
            'hasMore' => $result->hasMore,
        ];
    }
}
