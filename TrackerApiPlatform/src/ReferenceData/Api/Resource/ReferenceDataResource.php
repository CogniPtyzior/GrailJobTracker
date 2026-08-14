<?php

declare(strict_types=1);

/*
 * API Platform read resource for frontend reference values.
 * It exposes stable enum lists without copying the legacy controller or leaking domain objects.
 */

namespace App\ReferenceData\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\ReferenceData\Api\Output\ReferenceDataOutput;
use App\ReferenceData\Api\Provider\ReferenceDataProvider;

#[ApiResource(
    shortName: 'ReferenceData',
    operations: [
        new Get(
            uriTemplate: '/reference-data',
            output: ReferenceDataOutput::class,
            normalizationContext: ['groups' => ['reference_data:read']],
            read: false,
            provider: ReferenceDataProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'reference_data_get',
        ),
    ],
)]
final class ReferenceDataResource
{
}
