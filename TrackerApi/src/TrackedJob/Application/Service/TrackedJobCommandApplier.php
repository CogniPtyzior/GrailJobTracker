<?php

namespace App\TrackedJob\Application\Service;

use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;

final class TrackedJobCommandApplier
{
    public function apply(TrackedJob $trackedJob, TrackedJobCommand $command): void
    {
        $trackedJob->updatePosition(
            $command->company,
            $command->title,
            $command->contractType,
            $command->location,
            $command->remoteMode,
            $command->remuneration,
            $command->offerUrl,
            $command->notes,
        );
        $trackedJob->updateTimeline(TrackedJobTimeline::fromProcessDates(
            $command->applicationDate,
            $command->effectiveFollowUpDate,
            $command->firstContactDate,
            $command->preliminaryInterviewDate,
            $command->secondInterviewDate,
        ));
        $trackedJob->updateContacts($command->hrContactName, $command->businessContactName);
        $trackedJob->updateRelevance(
            $command->subjectiveRelevance !== null ? SubjectiveRelevance::fromInt($command->subjectiveRelevance) : null,
        );
        $trackedJob->requestStatus($command->status);
    }
}
