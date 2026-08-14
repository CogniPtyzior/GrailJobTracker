<?php

declare(strict_types=1);

/*
 * Mapper from API Platform export input to the application export filter object.
 * It is the boundary where frontend strings become typed domain enum filters.
 */

namespace App\TrackedJob\Api\Mapper;

use App\TrackedJob\Api\Input\ExportTrackedJobsInput as ApiExportTrackedJobsInput;
use App\TrackedJob\Application\Input\ExportTrackedJobsInput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

final readonly class TrackedJobExportInputMapper
{
    public function toApplicationInput(ApiExportTrackedJobsInput $input): ExportTrackedJobsInput
    {
        return new ExportTrackedJobsInput([
            'search' => $this->blankToNull($input->search),
            'company' => $this->blankToNull($input->company),
            'status' => $this->enumOrNull(TrackedJobStatus::class, $input->status),
            'contractType' => $this->enumOrNull(ContractType::class, $input->contractType),
            'remoteMode' => $this->enumOrNull(RemoteMode::class, $input->remoteMode),
        ]);
    }

    private function blankToNull(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
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
