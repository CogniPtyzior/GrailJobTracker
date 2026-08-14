<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job API input mapping.
 * They lock the transport-to-application boundary without involving API Platform runtime.
 */

use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Mapper\TrackedJobInputMapper;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;

it('maps frontend write payload values to an application command', function (): void {
    $input = new CreateTrackedJobInput();
    $input->company = ' Acme ';
    $input->title = ' Backend Engineer ';
    $input->contractType = 'CDD';
    $input->location = ' Paris ';
    $input->remoteMode = 'FULL';
    $input->remuneration = ' 60k ';
    $input->offerUrl = ' https://example.com/job ';
    $input->notes = ' Strong fit ';
    $input->applicationDate = '2026-07-28T00:00:00.000Z';
    $input->hrContactName = ' Jane HR ';
    $input->businessContactName = ' Bob Manager ';
    $input->subjectiveRelevance = '9';

    $command = (new TrackedJobInputMapper())->toCommand($input);

    expect($command->company?->value())->toBe('Acme')
        ->and($command->title?->value())->toBe('Backend Engineer')
        ->and($command->contractType)->toBe(ContractType::CDD)
        ->and($command->location)->toBe('Paris')
        ->and($command->remoteMode)->toBe(RemoteMode::FULL)
        ->and($command->remuneration)->toBe('60k')
        ->and($command->offerUrl?->value())->toBe('https://example.com/job')
        ->and($command->notes?->value())->toBe('Strong fit')
        ->and($command->applicationDate?->format(DateTimeInterface::ATOM))->toBe('2026-07-28T00:00:00+00:00')
        ->and($command->hrContactName?->value())->toBe('Jane HR')
        ->and($command->businessContactName?->value())->toBe('Bob Manager')
        ->and($command->subjectiveRelevance)->toBe(9);
});

it('maps blank optional strings to null where the legacy API accepted blanks', function (): void {
    $input = new CreateTrackedJobInput();
    $input->location = '   ';
    $input->remuneration = '   ';
    $input->offerUrl = '   ';
    $input->subjectiveRelevance = '';

    $command = (new TrackedJobInputMapper())->toCommand($input);

    expect($command->location)->toBeNull()
        ->and($command->remuneration)->toBeNull()
        ->and($command->offerUrl)->toBeNull()
        ->and($command->subjectiveRelevance)->toBeNull();
});