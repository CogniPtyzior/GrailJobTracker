<?php

declare(strict_types=1);

namespace App\TrackedJob\Presentation\Payload;

use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use App\TrackedJob\Application\ExportTrackedJobsInput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Typed DTO representing the tracked job CSV export filters accepted by the HTTP controller.
 */
#[Assert\Callback('validate')]
final readonly class ExportTrackedJobsPayload implements RequestPayload
{
    public function __construct(
        public ?string $search = null,
        public ?string $company = null,
        public ?string $status = null,
        public ?string $contractType = null,
        public ?string $remoteMode = null,
    ) {
    }

    /**
     * @return list<string>
     */
    public static function expectedFields(): array
    {
        return ['search', 'company', 'status', 'contractType', 'remoteMode'];
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        try {
            return new self(
                search: $payload['search'] ?? null,
                company: $payload['company'] ?? null,
                status: $payload['status'] ?? null,
                contractType: $payload['contractType'] ?? null,
                remoteMode: $payload['remoteMode'] ?? null,
            );
        } catch (\TypeError) {
            throw RequestPayloadHydrationException::invalidPayload();
        }
    }

    public function toInput(): ExportTrackedJobsInput
    {
        return new ExportTrackedJobsInput([
            'search' => $this->search,
            'company' => $this->company,
            'status' => $this->enumOrNull($this->status, TrackedJobStatus::class),
            'contractType' => $this->enumOrNull($this->contractType, ContractType::class),
            'remoteMode' => $this->enumOrNull($this->remoteMode, RemoteMode::class),
        ]);
    }

    public function validate(ExecutionContextInterface $context): void
    {
        $this->validateEnum('status', $this->status, TrackedJobStatus::class, $context);
        $this->validateEnum('contractType', $this->contractType, ContractType::class, $context);
        $this->validateEnum('remoteMode', $this->remoteMode, RemoteMode::class, $context);
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

    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    private function enumOrNull(?string $value, string $enumClass): ?\BackedEnum
    {
        return $value !== null ? $enumClass::tryFrom($value) : null;
    }
}

