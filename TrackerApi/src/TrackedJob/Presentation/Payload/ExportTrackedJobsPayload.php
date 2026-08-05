<?php

declare(strict_types=1);

namespace App\TrackedJob\Presentation\Payload;

use App\Shared\Infrastructure\Validation\RequestPayload;
use App\Shared\Infrastructure\Validation\RequestPayloadHydrationException;
use App\TrackedJob\Application\Input\ExportTrackedJobsInput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Typed DTO representing the tracked job CSV export filters accepted by the HTTP controller.
 */
final readonly class ExportTrackedJobsPayload implements RequestPayload
{
    public function __construct(
        public ?string $search = null,
        public ?string $company = null,
        #[Assert\Choice(callback: [TrackedJobStatus::class, 'values'])]
        public ?string $status = null,
        #[Assert\Choice(callback: [ContractType::class, 'values'])]
        public ?string $contractType = null,
        #[Assert\Choice(callback: [RemoteMode::class, 'values'])]
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

    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    private function enumOrNull(?string $value, string $enumClass): ?\BackedEnum
    {
        return $value !== null ? $enumClass::tryFrom($value) : null;
    }
}