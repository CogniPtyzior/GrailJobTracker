<?php

declare(strict_types=1);

/*
 * Unit tests for reference data output metadata.
 * They make the API Platform serializer group contract explicit and reviewable.
 */

use App\ReferenceData\Api\Output\ReferenceDataOutput;
use Symfony\Component\Serializer\Attribute\Groups;

it('exposes every reference data field through the read serialization group', function (): void {
    $reflection = new ReflectionClass(ReferenceDataOutput::class);
    $fields = ['contractTypes', 'remoteModes', 'trackedJobStatuses', 'defaultContractType'];

    foreach ($fields as $field) {
        $attributes = $reflection->getProperty($field)->getAttributes(Groups::class);

        expect($attributes)->toHaveCount(1)
            ->and($attributes[0]->newInstance()->groups)->toBe(['reference_data:read']);
    }
});
