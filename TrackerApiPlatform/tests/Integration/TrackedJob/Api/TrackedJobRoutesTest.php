<?php

declare(strict_types=1);

/*
 * Integration tests for tracked job API Platform route exposure.
 * They verify frontend-compatible paths and operation metadata for read and write routes.
 */

use ApiPlatform\Metadata\ApiResource;
use App\TrackedJob\Api\Resource\TrackedJobResource;
use Symfony\Component\Routing\RouterInterface;

it('exposes frontend-compatible tracked job read and write routes', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);

    $router->getContext()->setMethod('GET');
    expect($router->match('/api/tracked-jobs')['_route'])->toContain('tracked_job_list')
        ->and($router->match('/api/tracked-jobs/018f6d6f-0000-7000-8000-000000000004')['_route'])
        ->toContain('tracked_job_get');

    $router->getContext()->setMethod('POST');
    expect($router->match('/api/tracked-jobs')['_route'])->toContain('tracked_job_create');

    $router->getContext()->setMethod('PUT');
    expect($router->match('/api/tracked-jobs/018f6d6f-0000-7000-8000-000000000004')['_route'])
        ->toContain('tracked_job_update');

    $router->getContext()->setMethod('DELETE');
    expect($router->match('/api/tracked-jobs/018f6d6f-0000-7000-8000-000000000004')['_route'])
        ->toContain('tracked_job_delete');
});

it('combines envelope and shared read groups on tracked job output operations', function (): void {
    $operations = trackedJobOperationsByName();

    expect($operations['tracked_job_list']->getNormalizationContext())
        ->toBe(['groups' => ['tracked_job:list', 'tracked_job:read']])
        ->and($operations['tracked_job_create']->getNormalizationContext())
        ->toBe(['groups' => ['tracked_job:item', 'tracked_job:read']])
        ->and($operations['tracked_job_get']->getNormalizationContext())
        ->toBe(['groups' => ['tracked_job:item', 'tracked_job:read']])
        ->and($operations['tracked_job_update']->getNormalizationContext())
        ->toBe(['groups' => ['tracked_job:item', 'tracked_job:read']]);
});

/** @return array<string, \ApiPlatform\Metadata\Operation> */
function trackedJobOperationsByName(): array
{
    $attributes = (new ReflectionClass(TrackedJobResource::class))->getAttributes(ApiResource::class);
    $resource = $attributes[0]->newInstance();
    $operations = [];

    foreach ($resource->getOperations() as $operation) {
        $operations[(string) $operation->getName()] = $operation;
    }

    return $operations;
}