<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job enum vocabularies.
 * They protect the legacy values consumed by the frontend reference data endpoint.
 */

use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;

it('preserves contract type values and their order', function (): void {
    expect(ContractType::values())->toBe([
        'CDI',
        'CDD',
        'FREELANCE',
        'INTERNSHIP',
        'APPRENTICESHIP',
        'OTHER',
    ]);
});

it('preserves remote mode values and their order', function (): void {
    expect(RemoteMode::values())->toBe([
        'NON',
        'HYBRID',
        'FLEXIBLE_HYBRID',
        'FULL',
    ]);
});

it('preserves tracked job status values and final states', function (): void {
    expect(TrackedJobStatus::values())->toBe([
        'DRAFT',
        'APPLIED',
        'FOLLOW_UP_PENDING',
        'FOLLOW_UP_DONE',
        'FIRST_CONTACT',
        'PRELIMINARY_INTERVIEW',
        'SECOND_INTERVIEW',
        'OFFER_RECEIVED',
        'HIRED',
        'REJECTED',
        'WITHDRAWN',
    ]);

    expect(TrackedJobStatus::OFFER_RECEIVED->isFinal())->toBeTrue()
        ->and(TrackedJobStatus::HIRED->isFinal())->toBeTrue()
        ->and(TrackedJobStatus::REJECTED->isFinal())->toBeTrue()
        ->and(TrackedJobStatus::WITHDRAWN->isFinal())->toBeTrue()
        ->and(TrackedJobStatus::APPLIED->isFinal())->toBeFalse();
});
