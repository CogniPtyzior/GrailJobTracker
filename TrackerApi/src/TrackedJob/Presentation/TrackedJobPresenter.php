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
        $timeline = $trackedJob->timeline();

        return [
            'id' => $trackedJob->getId()->toRfc4122(),
            'company' => $trackedJob->company()?->value(),
            'title' => $trackedJob->title()?->value(),
            'contractType' => $trackedJob->getContractType()?->value,
            'location' => $trackedJob->getLocation(),
            'remoteMode' => $trackedJob->getRemoteMode()?->value,
            'remuneration' => $trackedJob->getRemuneration(),
            'offerUrl' => $trackedJob->offerUrl()?->value(),
            'notes' => $trackedJob->notes()?->value(),
            'applicationDate' => $timeline->applicationDate()?->format(\DateTimeInterface::ATOM),
            'plannedFollowUpDate' => $timeline->plannedFollowUpDate()?->format(\DateTimeInterface::ATOM),
            'effectiveFollowUpDate' => $timeline->effectiveFollowUpDate()?->format(\DateTimeInterface::ATOM),
            'firstContactDate' => $timeline->firstContactDate()?->format(\DateTimeInterface::ATOM),
            'preliminaryInterviewDate' => $timeline->preliminaryInterviewDate()?->format(\DateTimeInterface::ATOM),
            'secondInterviewDate' => $timeline->secondInterviewDate()?->format(\DateTimeInterface::ATOM),
            'hrContactName' => $trackedJob->hrContactName()?->value(),
            'businessContactName' => $trackedJob->businessContactName()?->value(),
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
