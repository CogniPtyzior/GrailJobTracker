<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job output and input serializer groups.
 * They make the API Platform contracts explicit across read, suggestions and export operations.
 */

use App\TrackedJob\Api\Input\ExportTrackedJobsInput;
use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
use App\TrackedJob\Api\Output\TrackedJobCompanySuggestionsOutput;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Output\TrackedJobOutput;
use Symfony\Component\Serializer\Attribute\Groups;

it('exposes collection envelope fields through the list group', function (): void {
    expectGroups(TrackedJobCollectionOutput::class, 'items', ['tracked_job:list']);
    expectGroups(TrackedJobCollectionOutput::class, 'page', ['tracked_job:list']);
    expectGroups(TrackedJobCollectionOutput::class, 'pageSize', ['tracked_job:list']);
    expectGroups(TrackedJobCollectionOutput::class, 'hasMore', ['tracked_job:list']);
});

it('exposes item envelope through the item group', function (): void {
    expectGroups(TrackedJobItemOutput::class, 'item', ['tracked_job:item']);
});

it('exposes tracked job fields through the shared read group', function (): void {
    expectGroups(TrackedJobOutput::class, 'id', ['tracked_job:read']);
    expectGroups(TrackedJobOutput::class, 'company', ['tracked_job:read']);
    expectGroups(TrackedJobOutput::class, 'offerUrl', ['tracked_job:read']);
    expectGroups(TrackedJobOutput::class, 'notes', ['tracked_job:read']);
    expectGroups(TrackedJobOutput::class, 'hrContactName', ['tracked_job:read']);
    expectGroups(TrackedJobOutput::class, 'businessContactName', ['tracked_job:read']);
});

it('keeps company suggestions in their own output group', function (): void {
    expectGroups(TrackedJobCompanySuggestionsOutput::class, 'items', ['tracked_job:suggestions']);
});

it('keeps CSV export filters in their own input group', function (): void {
    expectGroups(ExportTrackedJobsInput::class, 'search', ['tracked_job:export']);
    expectGroups(ExportTrackedJobsInput::class, 'company', ['tracked_job:export']);
    expectGroups(ExportTrackedJobsInput::class, 'status', ['tracked_job:export']);
    expectGroups(ExportTrackedJobsInput::class, 'contractType', ['tracked_job:export']);
    expectGroups(ExportTrackedJobsInput::class, 'remoteMode', ['tracked_job:export']);
});

function expectGroups(string $class, string $property, array $groups): void
{
    $attributes = (new ReflectionClass($class))->getProperty($property)->getAttributes(Groups::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->groups)->toBe($groups);
}
