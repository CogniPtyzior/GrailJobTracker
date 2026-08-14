<?php

declare(strict_types=1);

/*
 * Unit tests for the reference data provider.
 * They verify the frontend response shape and enum completeness without using persistence.
 */

use ApiPlatform\Metadata\Get;
use App\ReferenceData\Api\Provider\ReferenceDataProvider;

it('returns the frontend-compatible reference data output', function (): void {
    $output = (new ReferenceDataProvider())->provide(new Get());

    expect($output->contractTypes)->toBe([
        'CDI',
        'CDD',
        'FREELANCE',
        'INTERNSHIP',
        'APPRENTICESHIP',
        'OTHER',
    ])->and($output->remoteModes)->toBe([
        'NON',
        'HYBRID',
        'FLEXIBLE_HYBRID',
        'FULL',
    ])->and($output->trackedJobStatuses)->toBe([
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
    ])->and($output->defaultContractType)->toBe('CDI');
});
