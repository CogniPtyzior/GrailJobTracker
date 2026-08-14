<?php

declare(strict_types=1);

/*
 * Unit tests for the active user checker.
 * They verify inactive accounts are rejected by Symfony security before protected API access.
 */

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\ActiveUserChecker;
use App\Security\Infrastructure\Security\SecurityUser;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\Exception\DisabledException;

it('accepts active security users', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));

    (new ActiveUserChecker())->checkPreAuth(new SecurityUser($user));

    expect(true)->toBeTrue();
});

it('rejects inactive security users', function (): void {
    $user = new User(EmailAddress::fromString('john@example.com'));
    $user->deactivate();

    (new ActiveUserChecker())->checkPostAuth(new SecurityUser($user));
})->throws(DisabledException::class);
