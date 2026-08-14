<?php

declare(strict_types=1);

/*
 * API Platform resource exposing tracked job read operations.
 * It is separate from the domain aggregate so API metadata and serialization groups stay in the inbound adapter.
 */

namespace App\TrackedJob\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Provider\TrackedJobCollectionProvider;
use App\TrackedJob\Api\Provider\TrackedJobItemProvider;

#[ApiResource(
    shortName: 'TrackedJob',
    operations: [
        new Get(
            uriTemplate: '/tracked-jobs',
            output: TrackedJobCollectionOutput::class,
            normalizationContext: ['groups' => ['tracked_job:list', 'tracked_job:read']],
            read: false,
            provider: TrackedJobCollectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'tracked_job_list',
        ),
        new Get(
            uriTemplate: '/tracked-jobs/{id}',
            output: TrackedJobItemOutput::class,
            normalizationContext: ['groups' => ['tracked_job:item', 'tracked_job:read']],
            read: false,
            provider: TrackedJobItemProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'tracked_job_get',
        ),
    ],
)]
final class TrackedJobResource
{
}
