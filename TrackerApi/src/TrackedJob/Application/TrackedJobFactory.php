<?php

namespace App\TrackedJob\Application;

use App\Security\Domain\Entity\User;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

final class TrackedJobFactory
{
    public function __construct(private readonly TrackedJobStatusResolver $statusResolver)
    {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function create(User $owner, array $payload): TrackedJob
    {
        $trackedJob = new TrackedJob($owner);

        $this->hydrate($trackedJob, $payload);

        return $trackedJob;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function hydrate(TrackedJob $trackedJob, array $payload): void
    {
        $trackedJob->setCompany($this->stringOrNull($payload['company'] ?? null));
        $trackedJob->setTitle($this->stringOrNull($payload['title'] ?? null));
        $trackedJob->setContractType($this->enumOrDefault(ContractType::class, $payload['contractType'] ?? null, ContractType::CDI));
        $trackedJob->setLocation($this->stringOrNull($payload['location'] ?? null));
        $trackedJob->setRemoteMode($this->enumOrNull(RemoteMode::class, $payload['remoteMode'] ?? null));
        $trackedJob->setRemuneration($this->stringOrNull($payload['remuneration'] ?? null));
        $trackedJob->setOfferUrl($this->stringOrNull($payload['offerUrl'] ?? null));
        $trackedJob->setNotes($this->stringOrNull($payload['notes'] ?? null));
        $trackedJob->setApplicationDate($this->dateOrNull($payload['applicationDate'] ?? null));
        $trackedJob->setEffectiveFollowUpDate($this->dateOrNull($payload['effectiveFollowUpDate'] ?? null));
        $trackedJob->setFirstContactDate($this->dateOrNull($payload['firstContactDate'] ?? null));
        $trackedJob->setPreliminaryInterviewDate($this->dateOrNull($payload['preliminaryInterviewDate'] ?? null));
        $trackedJob->setSecondInterviewDate($this->dateOrNull($payload['secondInterviewDate'] ?? null));
        $trackedJob->setHrContactName($this->stringOrNull($payload['hrContactName'] ?? null));
        $trackedJob->setBusinessContactName($this->stringOrNull($payload['businessContactName'] ?? null));
        $trackedJob->setSubjectiveRelevance($this->intOrNull($payload['subjectiveRelevance'] ?? null));
        $trackedJob->setPlannedFollowUpDate($this->calculatePlannedFollowUpDate($trackedJob->getApplicationDate()));

        $requestedStatus = $this->enumOrNull(TrackedJobStatus::class, $payload['status'] ?? null);
        $this->statusResolver->recalculate($trackedJob, $requestedStatus);

        $trackedJob->touch();
    }

    private function calculatePlannedFollowUpDate(?\DateTimeImmutable $applicationDate): ?\DateTimeImmutable
    {
        if ($applicationDate === null) {
            return null;
        }

        return $applicationDate->setTime(0, 0)->modify('+15 days');
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function intOrNull(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @template T of \UnitEnum
     * @param class-string<T> $enumClass
     * @return T|null
     */
    private function enumOrNull(string $enumClass, mixed $value): ?\UnitEnum
    {
        if (!is_string($value) || $value === '') {
            return null;
        }

        return $enumClass::tryFrom($value);
    }

    /**
     * @template T of \UnitEnum
     * @param class-string<T> $enumClass
     * @param T $default
     * @return T
     */
    private function enumOrDefault(string $enumClass, mixed $value, \UnitEnum $default): \UnitEnum
    {
        $enum = $this->enumOrNull($enumClass, $value);

        return $enum ?? $default;
    }

    private function dateOrNull(mixed $value): ?\DateTimeImmutable
    {
        return TrackedJobDateParser::parseNullable($value);
    }
}