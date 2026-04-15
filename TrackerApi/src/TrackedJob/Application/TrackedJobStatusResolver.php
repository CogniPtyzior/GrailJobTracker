<?php

namespace App\TrackedJob\Application;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

final class TrackedJobStatusResolver
{
    public function recalculate(TrackedJob $trackedJob, ?TrackedJobStatus $requestedFinalStatus = null): void
    {
        if ($requestedFinalStatus?->isFinal()) {
            $trackedJob->setStatus($requestedFinalStatus);

            return;
        }

        $currentStatus = $trackedJob->getStatus();

        if ($currentStatus->isFinal() && $requestedFinalStatus === null) {
            return;
        }

        if ($trackedJob->getSecondInterviewDate() !== null) {
            $trackedJob->setStatus(TrackedJobStatus::SECOND_INTERVIEW);

            return;
        }

        if ($trackedJob->getPreliminaryInterviewDate() !== null) {
            $trackedJob->setStatus(TrackedJobStatus::PRELIMINARY_INTERVIEW);

            return;
        }

        if ($trackedJob->getFirstContactDate() !== null) {
            $trackedJob->setStatus(TrackedJobStatus::FIRST_CONTACT);

            return;
        }

        if ($trackedJob->getEffectiveFollowUpDate() !== null) {
            $trackedJob->setStatus(TrackedJobStatus::FOLLOW_UP_DONE);

            return;
        }

        if ($trackedJob->getPlannedFollowUpDate() !== null) {
            $trackedJob->setStatus(TrackedJobStatus::FOLLOW_UP_PENDING);

            return;
        }

        if ($trackedJob->getApplicationDate() !== null) {
            $trackedJob->setStatus(TrackedJobStatus::APPLIED);

            return;
        }

        $trackedJob->setStatus(TrackedJobStatus::DRAFT);
    }
}
