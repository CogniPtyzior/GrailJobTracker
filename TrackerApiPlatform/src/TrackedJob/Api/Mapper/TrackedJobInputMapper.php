<?php

declare(strict_types=1);

/*
 * Mapper from validated API Platform tracked job inputs to application commands.
 * It is the explicit boundary where transport strings become domain value objects and enums.
 */

namespace App\TrackedJob\Api\Mapper;

use App\TrackedJob\Api\Input\AbstractTrackedJobInput;
use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Date\TrackedJobDateParser;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;

final readonly class TrackedJobInputMapper
{
    public function toCommand(AbstractTrackedJobInput $input): TrackedJobCommand
    {
        return new TrackedJobCommand(
            company: CompanyName::fromNullable($input->company),
            title: JobTitle::fromNullable($input->title),
            contractType: $this->enumOrNull(ContractType::class, $input->contractType),
            location: $this->blankToNull($input->location),
            remoteMode: $this->enumOrNull(RemoteMode::class, $input->remoteMode),
            remuneration: $this->blankToNull($input->remuneration),
            offerUrl: OfferUrl::fromNullable($input->offerUrl),
            notes: TrackedJobNotes::fromNullable($input->notes),
            applicationDate: TrackedJobDateParser::parseNullable($input->applicationDate),
            effectiveFollowUpDate: TrackedJobDateParser::parseNullable($input->effectiveFollowUpDate),
            firstContactDate: TrackedJobDateParser::parseNullable($input->firstContactDate),
            preliminaryInterviewDate: TrackedJobDateParser::parseNullable($input->preliminaryInterviewDate),
            secondInterviewDate: TrackedJobDateParser::parseNullable($input->secondInterviewDate),
            hrContactName: ContactName::fromNullable($input->hrContactName),
            businessContactName: ContactName::fromNullable($input->businessContactName),
            subjectiveRelevance: $this->intOrNull($input->subjectiveRelevance),
            status: $this->enumOrNull(TrackedJobStatus::class, $input->status),
        );
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    private function intOrNull(int|float|string|null $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enumClass
     * @return T|null
     */
    private function enumOrNull(string $enumClass, ?string $value): ?\BackedEnum
    {
        if ($value === null || $value === '') {
            return null;
        }

        return $enumClass::from($value);
    }
}