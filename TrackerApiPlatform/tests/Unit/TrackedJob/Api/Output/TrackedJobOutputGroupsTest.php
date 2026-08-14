<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job output serializer groups.
 * They make the API Platform read contracts explicit and reviewable.
 */

use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
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

function expectGroups(string $class, string $property, array $groups): void
{
    $attributes = (new ReflectionClass($class))->getProperty($property)->getAttributes(Groups::class);

    expect($attributes)->toHaveCount(1)
        ->and($attributes[0]->newInstance()->groups)->toBe($groups);
}
