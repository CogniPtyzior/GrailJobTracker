<?php

declare(strict_types=1);

namespace App\TrackedJob\Presentation\Payload;

use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use App\TrackedJob\Application\TrackedJobDateParser;
use App\TrackedJob\Application\TrackedJobInput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Typed DTO representing the tracked job create/update payload accepted by the HTTP controller.
 */
#[Assert\Callback('validate')]
final readonly class TrackedJobPayload implements RequestPayload
{
    public function __construct(
        #[Assert\Length(max: 255)]
        public ?string $company = null,
        #[Assert\Length(max: 255)]
        public ?string $title = null,
        public ?string $contractType = null,
        #[Assert\Length(max: 255)]
        public ?string $location = null,
        public ?string $remoteMode = null,
        #[Assert\Length(max: 255)]
        public ?string $remuneration = null,
        public ?string $offerUrl = null,
        #[Assert\Length(max: 10000)]
        public ?string $notes = null,
        public ?string $applicationDate = null,
        public ?string $effectiveFollowUpDate = null,
        public ?string $firstContactDate = null,
        public ?string $preliminaryInterviewDate = null,
        public ?string $secondInterviewDate = null,
        #[Assert\Length(max: 255)]
        public ?string $hrContactName = null,
        #[Assert\Length(max: 255)]
        public ?string $businessContactName = null,
        public int|float|string|null $subjectiveRelevance = null,
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
        return new TrackedJobInput([
            'company' => $this->company,
            'title' => $this->title,
            'contractType' => $this->contractType,
            'location' => $this->location,
            'remoteMode' => $this->remoteMode,
            'remuneration' => $this->remuneration,
            'offerUrl' => $this->offerUrl,
            'notes' => $this->notes,
            'applicationDate' => $this->applicationDate,
            'effectiveFollowUpDate' => $this->effectiveFollowUpDate,
            'firstContactDate' => $this->firstContactDate,
            'preliminaryInterviewDate' => $this->preliminaryInterviewDate,
            'secondInterviewDate' => $this->secondInterviewDate,
            'hrContactName' => $this->hrContactName,
            'businessContactName' => $this->businessContactName,
            'subjectiveRelevance' => $this->subjectiveRelevance,
            'status' => $this->status,
        ]);
    }

    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateEnum('contractType', $this->contractType, ContractType::class, $context);
        $this->validateEnum('remoteMode', $this->remoteMode, RemoteMode::class, $context);
        $this->validateEnum('status', $this->status, TrackedJobStatus::class, $context);
        $this->validateSubjectiveRelevance($context);
        $this->validateDate('applicationDate', $this->applicationDate, $context);
        $this->validateDate('effectiveFollowUpDate', $this->effectiveFollowUpDate, $context);
        $this->validateDate('firstContactDate', $this->firstContactDate, $context);
        $this->validateDate('preliminaryInterviewDate', $this->preliminaryInterviewDate, $context);
        $this->validateDate('secondInterviewDate', $this->secondInterviewDate, $context);
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    private function validateEnum(
        string $field,
        ?string $value,
        string $enumClass,
        ExecutionContextInterface $context,
    ): void {
        if ($value === null) {
            return;
        }

        if ($enumClass::tryFrom($value) === null) {
            $context->buildViolation('The value you selected is not a valid choice.')
                ->atPath($field)
                ->addViolation();
        }
    }

    private function validateSubjectiveRelevance(ExecutionContextInterface $context): void
    {
        if ($this->subjectiveRelevance === null) {
            return;
        }

        if (!is_numeric($this->subjectiveRelevance)) {
            $context->buildViolation('This value should be of type numeric.')
                ->atPath('subjectiveRelevance')
                ->addViolation();

            return;
        }

        if ((float) $this->subjectiveRelevance < 1 || (float) $this->subjectiveRelevance > 10) {
            $context->buildViolation('This value should be between {{ min }} and {{ max }}.')
                ->setParameter('{{ min }}', '1')
                ->setParameter('{{ max }}', '10')
                ->atPath('subjectiveRelevance')
                ->addViolation();
        }
    }

    private function validateDate(string $field, ?string $value, ExecutionContextInterface $context): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!TrackedJobDateParser::isValid($value)) {
            $context->buildViolation('This value should be a valid date.')
                ->atPath($field)
                ->addViolation();
        }
    }
}

