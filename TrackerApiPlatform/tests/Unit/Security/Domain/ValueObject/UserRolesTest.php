<?php

declare(strict_types=1);

/*
 * Unit tests for user roles.
 * They preserve security role normalization before Symfony security integration is added.
 */

use App\Security\Domain\ValueObject\UserRoles;

it('creates regular user roles', function (): void {
    expect(UserRoles::regularUser()->toArray())->toBe(['ROLE_USER']);
});

it('creates admin roles while keeping ROLE_USER', function (): void {
    expect(UserRoles::admin()->toArray())->toBe(['ROLE_ADMIN', 'ROLE_USER']);
});

it('cleans persisted role lists', function (): void {
    $roles = UserRoles::fromArray([' ROLE_ADMIN ', '', 'ROLE_ADMIN', 'ROLE_USER']);

    expect($roles->toArray())->toBe(['ROLE_ADMIN', 'ROLE_USER']);
});

it('adds ROLE_USER when persisted roles omit it', function (): void {
    expect(UserRoles::fromArray(['ROLE_ADMIN'])->toArray())->toBe(['ROLE_ADMIN', 'ROLE_USER']);
});

it('defaults to ROLE_USER when persisted roles are empty', function (): void {
    expect(UserRoles::fromArray(['', '   '])->toArray())->toBe(['ROLE_USER']);
});
