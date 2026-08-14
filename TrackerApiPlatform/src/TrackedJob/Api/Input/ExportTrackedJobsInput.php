<?php

declare(strict_types=1);

/*
 * API Platform input DTO for tracked job CSV exports.
 * It uses a dedicated serializer group so export filters stay separate from read and write contracts.
 */

namespace App\TrackedJob\Api\Input;

use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ExportTrackedJobsInput
{
    #[Groups(['tracked_job:export'])]
    public ?string $search = null;

    #[Groups(['tracked_job:export'])]
    public ?string $company = null;

    #[Groups(['tracked_job:export'])]
    #[Assert\Choice(callback: [TrackedJobStatus::class, 'values'])]
    public ?string $status = null;

    #[Groups(['tracked_job:export'])]
    #[Assert\Choice(callback: [ContractType::class, 'values'])]
    public ?string $contractType = null;

    #[Groups(['tracked_job:export'])]
    #[Assert\Choice(callback: [RemoteMode::class, 'values'])]
    public ?string $remoteMode = null;
}
