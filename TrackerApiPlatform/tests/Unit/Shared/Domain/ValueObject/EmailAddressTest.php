<?php

declare(strict_types=1);

/*
 * Unit tests for shared email identity normalization.
 * They lock the legacy comparison semantics without adding validation rules prematurely.
 */

use App\Shared\Domain\ValueObject\EmailAddress;

it('keeps the original email value', function (): void {
    $email = EmailAddress::fromString('  User@Example.COM  ');

    expect($email->value())->toBe('  User@Example.COM  ')
        ->and((string) $email)->toBe('  User@Example.COM  ');
});

it('normalizes emails for comparisons', function (): void {
    $email = EmailAddress::fromString('  User@Example.COM  ');

    expect($email->normalizedValue())->toBe('user@example.com');
});

it('compares emails by normalized value', function (): void {
    $left = EmailAddress::fromString('User@Example.COM');
    $right = EmailAddress::fromString(' user@example.com ');

    expect($left->equals($right))->toBeTrue();
});

it('normalizes reconstituted email identities', function (): void {
    $email = EmailAddress::reconstitute('User@Example.COM', ' USER@EXAMPLE.COM ');

    expect($email->normalizedValue())->toBe('user@example.com');
});
