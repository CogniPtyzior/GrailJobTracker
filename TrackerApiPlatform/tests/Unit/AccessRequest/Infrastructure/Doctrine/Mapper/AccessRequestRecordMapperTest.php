<?php

declare(strict_types=1);

/*
 * Unit tests for the access request Doctrine mapper.
 * They protect the explicit boundary between the access request aggregate and the preserved database record.
 */

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestId;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\AccessRequest\Infrastructure\Doctrine\Entity\AccessRequestRecord;
use App\AccessRequest\Infrastructure\Doctrine\Mapper\AccessRequestRecordMapper;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Symfony\Component\Uid\Uuid;

it('maps a Doctrine record to the access request domain aggregate', function (): void {
    $record = accessRequestRecordFixture();

    $accessRequest = (new AccessRequestRecordMapper())->toDomain($record);

    expect($accessRequest->getId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000301')
        ->and($accessRequest->getEmail())->toBe('Applicant@Example.com')
        ->and($accessRequest->getNormalizedEmail())->toBe('applicant@example.com')
        ->and($accessRequest->companyName()->value())->toBe('Acme')
        ->and($accessRequest->reason()->value())->toBe('I need access to manage tracked job applications.')
        ->and($accessRequest->firstName()?->value())->toBe('Jane')
        ->and($accessRequest->lastName()?->value())->toBe('Doe')
        ->and($accessRequest->getCreatedAt()->format('c'))->toBe('2026-04-01T10:00:00+00:00');
});

it('updates a Doctrine record from the access request domain aggregate', function (): void {
    $record = new AccessRequestRecord();
    $accessRequest = AccessRequest::reconstitute(
        AccessRequestId::fromString('018f6d6f-0000-7000-8000-000000000302'),
        EmailAddress::fromString('  Person@Example.com  '),
        AccessRequestCompanyName::fromString('  Globex  '),
        AccessRequestReason::fromString('This requester needs access to review tracked job activity.'),
        PersonName::fromNullable('  John  '),
        PersonName::fromNullable('  Smith  '),
        new DateTimeImmutable('2026-04-02T11:30:00+00:00'),
    );

    (new AccessRequestRecordMapper())->updateRecord($accessRequest, $record);

    expect($record->getId()->toRfc4122())->toBe('018f6d6f-0000-7000-8000-000000000302')
        ->and($record->getEmail())->toBe('  Person@Example.com  ')
        ->and($record->getNormalizedEmail())->toBe('person@example.com')
        ->and($record->getCompanyName())->toBe('Globex')
        ->and($record->getReason())->toBe('This requester needs access to review tracked job activity.')
        ->and($record->getFirstName())->toBe('John')
        ->and($record->getLastName())->toBe('Smith')
        ->and($record->getCreatedAt()->format('c'))->toBe('2026-04-02T11:30:00+00:00');
});

function accessRequestRecordFixture(): AccessRequestRecord
{
    $record = new AccessRequestRecord();
    $record->setId(Uuid::fromString('018f6d6f-0000-7000-8000-000000000301'));
    $record->setEmail('Applicant@Example.com');
    $record->setNormalizedEmail('applicant@example.com');
    $record->setCompanyName('  Acme  ');
    $record->setReason('I need access to manage tracked job applications.');
    $record->setFirstName('  Jane  ');
    $record->setLastName('  Doe  ');
    $record->setCreatedAt(new DateTimeImmutable('2026-04-01T10:00:00+00:00'));

    return $record;
}
