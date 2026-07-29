<?php

namespace App\TrackedJob\Presentation\Payload;

use App\TrackedJob\Application\TrackedJobDateParser;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Defines the tracked job create/update payload contract.
 */
final class TrackedJobPayload
{
    public static function constraint(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'company' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'title' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'contractType' => new Assert\Optional([self::enumChoiceConstraint(ContractType::class)]),
                'location' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'remoteMode' => new Assert\Optional([self::enumChoiceConstraint(RemoteMode::class)]),
                'remuneration' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'offerUrl' => new Assert\Optional([new Assert\Type('string')]),
                'notes' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 10000)]),
                'applicationDate' => self::dateFieldConstraint(),
                'effectiveFollowUpDate' => self::dateFieldConstraint(),
                'firstContactDate' => self::dateFieldConstraint(),
                'preliminaryInterviewDate' => self::dateFieldConstraint(),
                'secondInterviewDate' => self::dateFieldConstraint(),
                'hrContactName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'businessContactName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 255)]),
                'subjectiveRelevance' => new Assert\Optional([
                    new Assert\Type('numeric'),
                    new Assert\Range(min: 1, max: 10),
                ]),
                'status' => new Assert\Optional([self::enumChoiceConstraint(TrackedJobStatus::class)]),
            ],
            allowMissingFields: true,
            allowExtraFields: false,
        );
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

    private static function dateFieldConstraint(): Assert\Optional
    {
        return new Assert\Optional([
            new Assert\Callback([self::class, 'validateDateValue']),
        ]);
    }

    public static function validateDateValue(mixed $value, ExecutionContextInterface $context): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_string($value) || !TrackedJobDateParser::isValid($value)) {
            $context->buildViolation('This value should be a valid date.')
                ->addViolation();
        }
    }
}
