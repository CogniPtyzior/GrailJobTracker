<?php

declare(strict_types=1);

/*
 * API Platform provider for static reference data.
 * It maps domain enum vocabularies to the frontend-compatible output DTO.
 */

namespace App\ReferenceData\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ReferenceData\Api\Output\ReferenceDataOutput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

/** @implements ProviderInterface<ReferenceDataOutput> */
final readonly class ReferenceDataProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): ReferenceDataOutput
    {
        return new ReferenceDataOutput(
            contractTypes: ContractType::values(),
            remoteModes: RemoteMode::values(),
            trackedJobStatuses: TrackedJobStatus::values(),
            defaultContractType: ContractType::CDI->value,
        );
    }
}
