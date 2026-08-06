<?php

namespace App\TrackedJob\Application\Factory;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Application\Input\TrackedJobInput;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;

final class TrackedJobFactory
{
    public function create(User $owner, TrackedJobInput $input): TrackedJob
    {
        $trackedJob = new TrackedJob($owner);

        $this->hydrate($trackedJob, $input);

        return $trackedJob;
    }

    public function hydrate(TrackedJob $trackedJob, TrackedJobInput $input): void
    {
        $trackedJob->updateDetails(
            $input->company,
            $input->title,
            $input->contractType ?? ContractType::CDI,
            $input->location,
            $input->remoteMode,
            $input->remuneration,
            $input->offerUrl,
            $input->notes,
        );
        $trackedJob->updateProcessDates(
            $input->applicationDate,
            $input->effectiveFollowUpDate,
            $input->firstContactDate,
            $input->preliminaryInterviewDate,
            $input->secondInterviewDate,
        );
        $trackedJob->updateContacts($input->hrContactName, $input->businessContactName);
        $trackedJob->updateSubjectiveRelevance(
            $input->subjectiveRelevance !== null ? SubjectiveRelevance::fromInt($input->subjectiveRelevance) : null,
        );
        $trackedJob->recalculateStatus($input->status);
        $trackedJob->touch();
    }
}
