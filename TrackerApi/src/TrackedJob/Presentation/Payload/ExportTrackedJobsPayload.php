<?php

namespace App\TrackedJob\Presentation\Payload;

use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the export filters accepted in the tracked jobs CSV payload.
 */
final class ExportTrackedJobsPayload
{
    public static function constraint(): Assert\Collection
    {
        return new Assert\Collection([
            'fields' => [
                'search' => new Assert\Optional([new Assert\Type('string')]),
                'company' => new Assert\Optional([new Assert\Type('string')]),
                'status' => new Assert\Optional([self::enumChoiceConstraint(TrackedJobStatus::class)]),
                'contractType' => new Assert\Optional([self::enumChoiceConstraint(ContractType::class)]),
                'remoteMode' => new Assert\Optional([self::enumChoiceConstraint(RemoteMode::class)]),
            ],
            'allowMissingFields' => true,
            'allowExtraFields' => false,
        ]);
    }

    /**
     * @param class-string<\BackedEnum> $enumClass
     */
    private static function enumChoiceConstraint(string $enumClass): Assert\Choice
    {
        return new Assert\Choice(
            choices: array_map(static fn (\BackedEnum $item) => $item->value, $enumClass::cases()),
        );
    }
}
