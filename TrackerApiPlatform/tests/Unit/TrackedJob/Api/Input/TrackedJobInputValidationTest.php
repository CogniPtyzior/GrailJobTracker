<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job API input validation.
 * They verify Symfony Validator constraints before processors map payloads to application commands.
 */

use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Input\ExportTrackedJobsInput;
use Symfony\Component\Validator\Validation;

it('accepts the frontend ISO date format', function (): void {
    $input = new CreateTrackedJobInput();
    $input->applicationDate = '2026-07-28T00:00:00.000Z';

    $violations = validatorViolations($input);

    expect($violations)->toHaveCount(0);
});

it('rejects invalid tracked job dates', function (): void {
    $input = new CreateTrackedJobInput();
    $input->applicationDate = '2026-02-31T00:00:00.000Z';

    $violations = validatorViolations($input);

    expect($violations)->toHaveCount(1)
        ->and($violations[0]->getPropertyPath())->toBe('applicationDate');
});

it('preserves legacy constraints on enums lengths URLs and relevance', function (): void {
    $input = new CreateTrackedJobInput();
    $input->company = str_repeat('a', 256);
    $input->contractType = 'INVALID';
    $input->remoteMode = 'INVALID';
    $input->offerUrl = 'not-a-url';
    $input->notes = str_repeat('a', 10001);
    $input->subjectiveRelevance = 11;
    $input->status = 'INVALID';

    $paths = array_map(
        static fn ($violation): string => $violation->getPropertyPath(),
        iterator_to_array(validatorViolations($input)),
    );

    expect($paths)->toContain('company')
        ->and($paths)->toContain('contractType')
        ->and($paths)->toContain('remoteMode')
        ->and($paths)->toContain('offerUrl')
        ->and($paths)->toContain('notes')
        ->and($paths)->toContain('subjectiveRelevance')
        ->and($paths)->toContain('status');
});


it('preserves export filter enum validation', function (): void {
    $input = new ExportTrackedJobsInput();
    $input->status = 'INVALID';
    $input->contractType = 'INVALID';
    $input->remoteMode = 'INVALID';

    $paths = array_map(
        static fn ($violation): string => $violation->getPropertyPath(),
        iterator_to_array(validatorViolations($input)),
    );

    expect($paths)->toContain('status')
        ->and($paths)->toContain('contractType')
        ->and($paths)->toContain('remoteMode');
});
function validatorViolations(object $input): \Symfony\Component\Validator\ConstraintViolationListInterface
{
    return Validation::createValidatorBuilder()
        ->enableAttributeMapping()
        ->getValidator()
        ->validate($input);
}
