<?php

namespace App\Tests\Unit\TrackedJob\Domain\Entity;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Uuid;

final class TrackedJobTest extends TestCase
{
    public function testConstructorInitializesDefaults(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());

        self::assertSame(TrackedJobStatus::DRAFT, $trackedJob->getStatus());
        self::assertSame(ContractType::CDI, $trackedJob->getContractType());
        self::assertNotNull($trackedJob->getId());
        self::assertNotNull($trackedJob->getCreatedAt());
        self::assertNotNull($trackedJob->getUpdatedAt());
    }

    public function testUpdateDetailsNormalizesAllTextFieldsAndKeepsEnums(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());

        $trackedJob->updateDetails(
            '  Acme  ',
            '  Backend Engineer  ',
            ContractType::CDD,
            '  Paris  ',
            RemoteMode::FULL,
            '  60k  ',
            '  https://example.com/job  ',
            '  Strong fit  ',
        );

        self::assertSame('Acme', $trackedJob->getCompany());
        self::assertSame('Backend Engineer', $trackedJob->getTitle());
        self::assertSame(ContractType::CDD, $trackedJob->getContractType());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertSame(RemoteMode::FULL, $trackedJob->getRemoteMode());
        self::assertSame('60k', $trackedJob->getRemuneration());
        self::assertSame('https://example.com/job', $trackedJob->getOfferUrl());
        self::assertSame('Strong fit', $trackedJob->getNotes());
    }

    public function testUpdateDetailsConvertsBlankStringsToNullAndDefaultsContractType(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());

        $trackedJob->updateDetails('   ', '   ', null, '   ', null, '   ', '   ', '   ');

        self::assertNull($trackedJob->getCompany());
        self::assertNull($trackedJob->getTitle());
        self::assertSame(ContractType::CDI, $trackedJob->getContractType());
        self::assertNull($trackedJob->getLocation());
        self::assertNull($trackedJob->getRemoteMode());
        self::assertNull($trackedJob->getRemuneration());
        self::assertNull($trackedJob->getOfferUrl());
        self::assertNull($trackedJob->getNotes());
    }

    public function testUpdateProcessDatesStoresDatesAndComputesPlannedFollowUpAtUtcMidnight(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());
        $applicationDate = new \DateTimeImmutable('2026-04-01T14:30:00+00:00');

        $trackedJob->updateProcessDates(
            $applicationDate,
            FixedDates::april5(),
            FixedDates::april10(),
            FixedDates::april15(),
            FixedDates::april20(),
        );

        self::assertSame($applicationDate, $trackedJob->getApplicationDate());
        self::assertEquals(FixedDates::april5(), $trackedJob->getEffectiveFollowUpDate());
        self::assertEquals(FixedDates::april10(), $trackedJob->getFirstContactDate());
        self::assertEquals(FixedDates::april15(), $trackedJob->getPreliminaryInterviewDate());
        self::assertEquals(FixedDates::april20(), $trackedJob->getSecondInterviewDate());
        self::assertSame('2026-04-16T00:00:00+00:00', $trackedJob->getPlannedFollowUpDate()?->format('c'));
    }

    public function testUpdateProcessDatesClearsPlannedFollowUpWhenApplicationDateIsNull(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());
        $trackedJob->updateProcessDates(FixedDates::april1(), null, null, null, null);

        self::assertNotNull($trackedJob->getPlannedFollowUpDate());

        $trackedJob->updateProcessDates(null, null, null, null, null);

        self::assertNull($trackedJob->getApplicationDate());
        self::assertNull($trackedJob->getPlannedFollowUpDate());
    }

    public function testUpdateContactsNormalizesNames(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());

        $trackedJob->updateContacts('  Jane HR  ', '   ');

        self::assertSame('Jane HR', $trackedJob->getHrContactName());
        self::assertNull($trackedJob->getBusinessContactName());
    }

    public function testUpdateSubjectiveRelevanceStoresValueAndAllowsNull(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());

        $trackedJob->updateSubjectiveRelevance(8);
        self::assertSame(8, $trackedJob->getSubjectiveRelevance());

        $trackedJob->updateSubjectiveRelevance(null);
        self::assertNull($trackedJob->getSubjectiveRelevance());
    }

    public function testTouchUpdatesUpdatedAt(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());
        $property = new \ReflectionProperty($trackedJob, 'updatedAt');
        $oldValue = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $property->setValue($trackedJob, $oldValue);

        $trackedJob->touch();

        self::assertNotSame($oldValue, $trackedJob->getUpdatedAt());
        self::assertGreaterThan($oldValue, $trackedJob->getUpdatedAt());
    }

    public function testReconstituteRestoresPersistedStateWithoutRecomputingBusinessState(): void
    {
        $id = Uuid::fromString('018f6d6f-0000-7000-8000-000000000004');
        $owner = UserBuilder::aUser()->withEmail('owner@example.com')->build();
        $createdAt = new \DateTimeImmutable('2026-04-01T10:00:00+00:00');
        $updatedAt = new \DateTimeImmutable('2026-04-20T12:30:00+00:00');
        $applicationDate = new \DateTimeImmutable('2026-04-02T09:00:00+00:00');
        $plannedFollowUpDate = new \DateTimeImmutable('2026-04-17T00:00:00+00:00');
        $effectiveFollowUpDate = new \DateTimeImmutable('2026-04-10T11:00:00+00:00');
        $firstContactDate = new \DateTimeImmutable('2026-04-12T11:00:00+00:00');
        $preliminaryInterviewDate = new \DateTimeImmutable('2026-04-15T11:00:00+00:00');
        $secondInterviewDate = new \DateTimeImmutable('2026-04-18T11:00:00+00:00');

        $trackedJob = TrackedJob::reconstitute(
            $id,
            $owner,
            '  Acme  ',
            '  Backend Engineer  ',
            ContractType::CDD,
            '  Paris  ',
            RemoteMode::HYBRID,
            '  60k  ',
            '  https://example.com/job  ',
            '  Strong fit  ',
            $applicationDate,
            $plannedFollowUpDate,
            $effectiveFollowUpDate,
            $firstContactDate,
            $preliminaryInterviewDate,
            $secondInterviewDate,
            '  Jane HR  ',
            '   ',
            8,
            TrackedJobStatus::HIRED,
            $createdAt,
            $updatedAt,
        );

        self::assertSame($id, $trackedJob->getId());
        self::assertSame($owner, $trackedJob->getOwner());
        self::assertSame('Acme', $trackedJob->getCompany());
        self::assertSame('Backend Engineer', $trackedJob->getTitle());
        self::assertSame(ContractType::CDD, $trackedJob->getContractType());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertSame(RemoteMode::HYBRID, $trackedJob->getRemoteMode());
        self::assertSame('60k', $trackedJob->getRemuneration());
        self::assertSame('https://example.com/job', $trackedJob->getOfferUrl());
        self::assertSame('Strong fit', $trackedJob->getNotes());
        self::assertSame($applicationDate, $trackedJob->getApplicationDate());
        self::assertSame($plannedFollowUpDate, $trackedJob->getPlannedFollowUpDate());
        self::assertSame($effectiveFollowUpDate, $trackedJob->getEffectiveFollowUpDate());
        self::assertSame($firstContactDate, $trackedJob->getFirstContactDate());
        self::assertSame($preliminaryInterviewDate, $trackedJob->getPreliminaryInterviewDate());
        self::assertSame($secondInterviewDate, $trackedJob->getSecondInterviewDate());
        self::assertSame('Jane HR', $trackedJob->getHrContactName());
        self::assertNull($trackedJob->getBusinessContactName());
        self::assertSame(8, $trackedJob->getSubjectiveRelevance());
        self::assertSame(TrackedJobStatus::HIRED, $trackedJob->getStatus());
        self::assertSame($createdAt, $trackedJob->getCreatedAt());
        self::assertSame($updatedAt, $trackedJob->getUpdatedAt());
    }
}

