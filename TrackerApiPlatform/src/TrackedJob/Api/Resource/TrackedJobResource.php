<?php

declare(strict_types=1);

/*
 * API Platform resource exposing tracked job read and write operations.
 * It is separate from the domain aggregate so API metadata and serialization groups stay in the inbound adapter.
 */

namespace App\TrackedJob\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Input\UpdateTrackedJobInput;
use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Processor\CreateTrackedJobProcessor;
use App\TrackedJob\Api\Processor\DeleteTrackedJobProcessor;
use App\TrackedJob\Api\Processor\UpdateTrackedJobProcessor;
use App\TrackedJob\Api\Provider\TrackedJobCollectionProvider;
use App\TrackedJob\Api\Provider\TrackedJobItemProvider;
use Symfony\Component\HttpFoundation\Response;

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
        new Post(
            uriTemplate: '/tracked-jobs',
            input: CreateTrackedJobInput::class,
            output: TrackedJobItemOutput::class,
            normalizationContext: ['groups' => ['tracked_job:item', 'tracked_job:read']],
            read: false,
            processor: CreateTrackedJobProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            status: Response::HTTP_CREATED,
            name: 'tracked_job_create',
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
        new Put(
            uriTemplate: '/tracked-jobs/{id}',
            input: UpdateTrackedJobInput::class,
            output: TrackedJobItemOutput::class,
            normalizationContext: ['groups' => ['tracked_job:item', 'tracked_job:read']],
            read: false,
            processor: UpdateTrackedJobProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'tracked_job_update',
        ),
        new Delete(
            uriTemplate: '/tracked-jobs/{id}',
            output: false,
            read: false,
            processor: DeleteTrackedJobProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            status: Response::HTTP_NO_CONTENT,
            name: 'tracked_job_delete',
        ),
    ],
)]
final class TrackedJobResource
{
}