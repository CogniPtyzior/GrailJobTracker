<?php

declare(strict_types=1);

/*
 * API Platform resource exposing tracked job operations.
 * It stays separate from the domain aggregate so API metadata and serializer groups remain in the inbound adapter.
 */

namespace App\TrackedJob\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Input\ExportTrackedJobsInput;
use App\TrackedJob\Api\Input\UpdateTrackedJobInput;
use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
use App\TrackedJob\Api\Output\TrackedJobCompanySuggestionsOutput;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Processor\CreateTrackedJobProcessor;
use App\TrackedJob\Api\Processor\DeleteTrackedJobProcessor;
use App\TrackedJob\Api\Processor\ExportTrackedJobsCsvProcessor;
use App\TrackedJob\Api\Processor\UpdateTrackedJobProcessor;
use App\TrackedJob\Api\Provider\TrackedJobCollectionProvider;
use App\TrackedJob\Api\Provider\TrackedJobCompanySuggestionsProvider;
use App\TrackedJob\Api\Provider\TrackedJobItemProvider;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'TrackedJob',
    operations: [
        new Get(
            uriTemplate: '/tracked-jobs',
            output: TrackedJobCollectionOutput::class,
            normalizationContext: ['groups' => ['tracked_job:list', 'tracked_job:read']],
            provider: TrackedJobCollectionProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'tracked_job_list',
        ),
        new Get(
            uriTemplate: '/tracked-jobs/company-suggestions',
            output: TrackedJobCompanySuggestionsOutput::class,
            normalizationContext: ['groups' => ['tracked_job:suggestions']],
            provider: TrackedJobCompanySuggestionsProvider::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'tracked_job_company_suggestions',
        ),
        new Post(
            uriTemplate: '/tracked-jobs/export-csv',
            input: ExportTrackedJobsInput::class,
            output: false,
            inputFormats: ['json' => ['application/json']],
            outputFormats: ['csv' => ['text/csv']],
            denormalizationContext: ['groups' => ['tracked_job:export']],
            read: false,
            processor: ExportTrackedJobsCsvProcessor::class,
            security: "is_granted('IS_AUTHENTICATED_FULLY')",
            name: 'tracked_job_export_csv',
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



