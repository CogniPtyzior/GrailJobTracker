<?php

declare(strict_types=1);

/*
 * Integration tests for admin user API Platform route exposure.
 * They verify frontend-compatible paths and explicit serializer groups on admin operations.
 */

use ApiPlatform\Metadata\ApiResource;
use App\Security\Api\Resource\AdminUserResource;
use Symfony\Component\Routing\RouterInterface;

it('exposes frontend-compatible admin user routes', function (): void {
    self::bootKernel();

    /** @var RouterInterface $router */
    $router = self::getContainer()->get(RouterInterface::class);

    $router->getContext()->setMethod('GET');
    expect($router->match('/api/admin/users')['_route'])->toContain('admin_user_list');

    $router->getContext()->setMethod('POST');
    expect($router->match('/api/admin/users')['_route'])->toContain('admin_user_create');

    $router->getContext()->setMethod('PUT');
    expect($router->match('/api/admin/users/018f6d6f-0000-7000-8000-000000000901')['_route'])
        ->toContain('admin_user_update');

    $router->getContext()->setMethod('DELETE');
    expect($router->match('/api/admin/users/018f6d6f-0000-7000-8000-000000000901')['_route'])
        ->toContain('admin_user_delete');
});

it('declares separated admin user read and write groups', function (): void {
    $operations = adminUserOperationsByName();

    expect($operations['admin_user_list']->getNormalizationContext())
        ->toBe(['groups' => ['admin_user:list', 'admin_user:read']])
        ->and($operations['admin_user_create']->getDenormalizationContext())->toBe(['groups' => ['admin_user:create']])
        ->and($operations['admin_user_create']->getNormalizationContext())->toBe(['groups' => ['admin_user:read']])
        ->and($operations['admin_user_update']->getDenormalizationContext())->toBe(['groups' => ['admin_user:update']])
        ->and($operations['admin_user_update']->getNormalizationContext())->toBe(['groups' => ['admin_user:read']])
        ->and($operations['admin_user_delete']->getOutput())->toBeFalse();
});

/** @return array<string, \ApiPlatform\Metadata\Operation> */
function adminUserOperationsByName(): array
{
    $attributes = (new ReflectionClass(AdminUserResource::class))->getAttributes(ApiResource::class);
    $resource = $attributes[0]->newInstance();
    $operations = [];

    foreach ($resource->getOperations() as $operation) {
        $operations[(string) $operation->getName()] = $operation;
    }

    return $operations;
}
