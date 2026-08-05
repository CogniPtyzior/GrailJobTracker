<?php

declare(strict_types=1);

namespace App\TrackedJob\Presentation\Payload;

use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use App\TrackedJob\Application\Date\TrackedJobDateParser;
use App\TrackedJob\Application\Input\TrackedJobInput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Presentation\Validation\ValidTrackedJobDate;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Typed DTO representing the tracked job create/update payload accepted by the HTTP controller.
 */
final readonly class TrackedJobPayload implements RequestPayload
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $company = null,
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        #[Assert\Choice(callback: [ContractType::class, 'values'])]
        public ?string $contractType = null,
        #[Assert\Length(max: 255)]
        public ?string $location = null,
        #[Assert\Choice(callback: [RemoteMode::class, 'values'])]
        public ?string $remoteMode = null,
        #[Assert\Length(max: 255)]
        public ?string $remuneration = null,
        public ?string $offerUrl = null,
        #[Assert\Length(max: 10000)]
        public ?string $notes = null,
        #[ValidTrackedJobDate]
        public ?string $applicationDate = null,
        #[ValidTrackedJobDate]
        public ?string $effectiveFollowUpDate = null,
        #[ValidTrackedJobDate]
        public ?string $firstContactDate = null,
        #[ValidTrackedJobDate]
        public ?string $preliminaryInterviewDate = null,
        #[ValidTrackedJobDate]
        public ?string $secondInterviewDate = null,
        #[Assert\Length(max: 255)]
        public ?string $hrContactName = null,
        #[Assert\Length(max: 255)]
        public ?string $businessContactName = null,
        #[Assert\Type(type: 'numeric')]
        #[Assert\Range(min: 1, max: 10)]
        public int|float|string|null $subjectiveRelevance = null,
        #[Assert\Choice(callback: [TrackedJobStatus::class, 'values'])]
        public ?string $status = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function expectedFields(): array
    {
        return [
            'company',
            'title',
            'contractType',
            'location',
            'remoteMode',
            'remuneration',
            'offerUrl',
            'notes',
            'applicationDate',
            'effectiveFollowUpDate',
            'firstContactDate',
            'preliminaryInterviewDate',
            'secondInterviewDate',
            'hrContactName',
            'businessContactName',
            'subjectiveRelevance',
            'status',
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            return new self(
                company: $payload['company'] ?? null,
                title: $payload['title'] ?? null,
                contractType: $payload['contractType'] ?? null,
                location: $payload['location'] ?? null,
                remoteMode: $payload['remoteMode'] ?? null,
                remuneration: $payload['remuneration'] ?? null,
                offerUrl: $payload['offerUrl'] ?? null,
                notes: $payload['notes'] ?? null,
                applicationDate: $payload['applicationDate'] ?? null,
                effectiveFollowUpDate: $payload['effectiveFollowUpDate'] ?? null,
                firstContactDate: $payload['firstContactDate'] ?? null,
                preliminaryInterviewDate: $payload['preliminaryInterviewDate'] ?? null,
                secondInterviewDate: $payload['secondInterviewDate'] ?? null,
                hrContactName: $payload['hrContactName'] ?? null,
                businessContactName: $payload['businessContactName'] ?? null,
                subjectiveRelevance: $payload['subjectiveRelevance'] ?? null,
                status: $payload['status'] ?? null,
            );
        } catch (\TypeError) {
            throw RequestPayloadHydrationException::invalidPayload();
        }
    }

    public function toInput(): TrackedJobInput
    {
        return new TrackedJobInput(
            company: $this->blankToNull($this->company),
            title: $this->blankToNull($this->title),
            contractType: $this->enumOrNull(ContractType::class, $this->contractType),
            location: $this->blankToNull($this->location),
            remoteMode: $this->enumOrNull(RemoteMode::class, $this->remoteMode),
            remuneration: $this->blankToNull($this->remuneration),
            offerUrl: $this->blankToNull($this->offerUrl),
            notes: $this->blankToNull($this->notes),
            applicationDate: TrackedJobDateParser::parseNullable($this->applicationDate),
            effectiveFollowUpDate: TrackedJobDateParser::parseNullable($this->effectiveFollowUpDate),
            firstContactDate: TrackedJobDateParser::parseNullable($this->firstContactDate),
            preliminaryInterviewDate: TrackedJobDateParser::parseNullable($this->preliminaryInterviewDate),
            secondInterviewDate: TrackedJobDateParser::parseNullable($this->secondInterviewDate),
            hrContactName: $this->blankToNull($this->hrContactName),
            businessContactName: $this->blankToNull($this->businessContactName),
            subjectiveRelevance: $this->intOrNull($this->subjectiveRelevance),
            status: $this->enumOrNull(TrackedJobStatus::class, $this->status),
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