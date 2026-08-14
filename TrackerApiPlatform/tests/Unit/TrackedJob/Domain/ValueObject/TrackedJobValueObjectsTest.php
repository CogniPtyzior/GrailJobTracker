<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job value objects.
 * They cover normalization and invariant failures before API inputs or persistence mappers consume the values.
 */

use App\Shared\Domain\Exception\InvalidDomainData;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;

it('normalizes optional text value objects', function (): void {
    expect(CompanyName::fromNullable(null))->toBeNull()
        ->and(CompanyName::fromNullable('   '))->toBeNull()
        ->and(CompanyName::fromNullable('  Acme  ')?->value())->toBe('Acme')
        ->and(JobTitle::fromNullable('  Backend Engineer  ')?->value())->toBe('Backend Engineer')
        ->and(ContactName::fromNullable('  Jane HR  ')?->value())->toBe('Jane HR')
        ->and(TrackedJobNotes::fromNullable('  Strong fit  ')?->value())->toBe('Strong fit');
});

it('rejects text values above their maximum length', function (string $class, int $length, string $message): void {
    $class::fromNullable(str_repeat('a', $length));
})->with([
    [CompanyName::class, 256, 'Company name cannot exceed 255 characters.'],
    [JobTitle::class, 256, 'Job title cannot exceed 255 characters.'],
    [ContactName::class, 256, 'Contact name cannot exceed 255 characters.'],
    [TrackedJobNotes::class, 10001, 'Tracked job notes cannot exceed 10000 characters.'],
])->throws(InvalidDomainData::class);

it('normalizes and validates optional offer URLs', function (): void {
    expect(OfferUrl::fromNullable(null))->toBeNull()
        ->and(OfferUrl::fromNullable('   '))->toBeNull()
        ->and(OfferUrl::fromNullable('  https://example.com/job  ')?->value())->toBe('https://example.com/job');

    OfferUrl::fromNullable('not-a-url');
})->throws(InvalidDomainData::class, 'Offer URL must be a valid URL.');

it('validates subjective relevance range and equality', function (): void {
    $relevance = SubjectiveRelevance::fromInt(8);
    $same = SubjectiveRelevance::fromInt(8);
    $other = SubjectiveRelevance::fromInt(9);

    expect($relevance->value())->toBe(8)
        ->and($relevance->equals($same))->toBeTrue()
        ->and($relevance->equals($other))->toBeFalse();
});

it('rejects subjective relevance outside the accepted range', function (int $value): void {
    SubjectiveRelevance::fromInt($value);
})->with([0, 11])->throws(InvalidDomainData::class, 'Subjective relevance must be between 1 and 10.');

it('generates and restores tracked job identifiers', function (): void {
    $id = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004');

    expect(TrackedJobId::new()->toRfc4122())->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/')
        ->and($id->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000004')
        ->and((string) $id)->toBe('018f6d6f-0000-7000-8000-000000000004')
        ->and($id->equals(TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004')))->toBeTrue();
});

it('rejects invalid tracked job identifiers', function (): void {
    TrackedJobId::fromString('not-a-uuid');
})->throws(InvalidDomainData::class, 'Tracked job id must be a valid UUID.');
