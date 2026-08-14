<?php

declare(strict_types=1);

/*
 * Base API Platform input DTO for tracked job write payloads.
 * It preserves frontend field names while Symfony Validator enforces transport-level constraints.
 */

namespace App\TrackedJob\Api\Input;

use App\TrackedJob\Application\Date\TrackedJobDateParser;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

abstract class AbstractTrackedJobInput
{
    #[Assert\Length(max: 255)]
    public ?string $company = null;

    #[Assert\Length(max: 255)]
    public ?string $title = null;

    #[Assert\Choice(callback: [ContractType::class, 'values'])]
    public ?string $contractType = null;

    #[Assert\Length(max: 255)]
    public ?string $location = null;

    #[Assert\Choice(callback: [RemoteMode::class, 'values'])]
    public ?string $remoteMode = null;

    #[Assert\Length(max: 255)]
    public ?string $remuneration = null;

    #[Assert\Url(requireTld: false, normalizer: 'trim')]
    public ?string $offerUrl = null;

    #[Assert\Length(max: 10000)]
    public ?string $notes = null;

    public ?string $applicationDate = null;

    public ?string $effectiveFollowUpDate = null;

    public ?string $firstContactDate = null;

    public ?string $preliminaryInterviewDate = null;

    public ?string $secondInterviewDate = null;

    #[Assert\Length(max: 255)]
    public ?string $hrContactName = null;

    #[Assert\Length(max: 255)]
    public ?string $businessContactName = null;

    #[Assert\Type(type: 'numeric')]
    #[Assert\Range(min: 1, max: 10)]
    public int|float|string|null $subjectiveRelevance = null;

    #[Assert\Choice(callback: [TrackedJobStatus::class, 'values'])]
    public ?string $status = null;

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        foreach ($this->dateFields() as $field) {
            $value = $this->{$field};

            if ($value === null || trim($value) === '') {
                continue;
            }

            if (!TrackedJobDateParser::isValid($value)) {
                $context->buildViolation('This value is not a valid tracked job date.')
                    ->atPath($field)
                    ->addViolation();
            }
        }
    }

    /** @return list<string> */
    private function dateFields(): array
    {
        return [
            'applicationDate',
            'effectiveFollowUpDate',
            'firstContactDate',
            'preliminaryInterviewDate',
            'secondInterviewDate',
        ];
    }
}