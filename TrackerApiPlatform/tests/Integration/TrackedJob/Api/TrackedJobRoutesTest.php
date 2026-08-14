<?php

declare(strict_types=1);

/*
 * Integration tests for tracked job API Platform route exposure.
 * They verify frontend-compatible read paths before write operations are migrated.
 */

use ApiPlatform\Metadata\ApiResource;
use App\TrackedJob\Api\Resource\TrackedJobResource;
use Symfony\Component\Routing\RouterInterface;

it('exposes tracked job collection and item read routes', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);
    $router->getContext()->setMethod('GET');

    expect($router->match('/api/tracked-jobs')['_route'])->toContain('tracked_job_list')
        ->and($router->match('/api/tracked-jobs/018f6d6f-0000-7000-8000-000000000004')['_route'])
        ->toContain('tracked_job_get');
});

it('combines envelope and shared read groups on tracked job read operations', function (): void {
    $attributes = (new ReflectionClass(TrackedJobResource::class))->getAttributes(ApiResource::class);
    $resource = $attributes[0]->newInstance();
    $operations = array_values(iterator_to_array($resource->getOperations()));

    expect($operations[0]->getNormalizationContext())->toBe(['groups' => ['tracked_job:list', 'tracked_job:read']])
        ->and($operations[1]->getNormalizationContext())->toBe(['groups' => ['tracked_job:item', 'tracked_job:read']]);
});