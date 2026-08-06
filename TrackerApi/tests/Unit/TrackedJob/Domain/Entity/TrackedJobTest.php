<?php

namespace App\Tests\Unit\TrackedJob\Domain\Entity;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\SubjectiveRelevance;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use App\TrackedJob\Domain\ValueObject\TrackedJobTimeline;

final class TrackedJobTest extends TestCase
{
    public function testConstructorInitializesDefaults(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());

        self::assertSame(TrackedJobStatus::DRAFT, $trackedJob->getStatus());
        self::assertSame(ContractType::CDI, $trackedJob->getContractType());
        self::assertNotNull($trackedJob->getId());
        self::assertNotNull($trackedJob->getCreatedAt());
        self::assertNotNull($trackedJob->getUpdatedAt());
    }

    public function testUpdatePositionNormalizesAllTextFieldsAndKeepsEnums(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());

        $trackedJob->updatePosition(
            CompanyName::fromNullable('  Acme  '),
            JobTitle::fromNullable('  Backend Engineer  '),
            ContractType::CDD,
            '  Paris  ',
            RemoteMode::FULL,
            '  60k  ',
            OfferUrl::fromNullable('  https://example.com/job  '),
            TrackedJobNotes::fromNullable('  Strong fit  '),
        );

        self::assertSame('Acme', $trackedJob->company()?->value());
        self::assertSame('Backend Engineer', $trackedJob->title()?->value());
        self::assertSame(ContractType::CDD, $trackedJob->getContractType());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertSame(RemoteMode::FULL, $trackedJob->getRemoteMode());
        self::assertSame('60k', $trackedJob->getRemuneration());
        self::assertSame('https://example.com/job', $trackedJob->offerUrl()?->value());
        self::assertSame('Strong fit', $trackedJob->notes()?->value());
    }

    public function testUpdatePositionConvertsBlankStringsToNullAndDefaultsContractType(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());

        $trackedJob->updatePosition(CompanyName::fromNullable('   '), JobTitle::fromNullable('   '), null, '   ', null, '   ', OfferUrl::fromNullable('   '), TrackedJobNotes::fromNullable('   '));

        self::assertNull($trackedJob->company()?->value());
        self::assertNull($trackedJob->title()?->value());
        self::assertSame(ContractType::CDI, $trackedJob->getContractType());
        self::assertNull($trackedJob->getLocation());
        self::assertNull($trackedJob->getRemoteMode());
        self::assertNull($trackedJob->getRemuneration());
        self::assertNull($trackedJob->offerUrl()?->value());
        self::assertNull($trackedJob->notes()?->value());
    }

    public function testUpdateTimelineStoresDatesAndComputesPlannedFollowUpAtUtcMidnight(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());
        $applicationDate = new \DateTimeImmutable('2026-04-01T14:30:00+00:00');

        $trackedJob->updateTimeline(TrackedJobTimeline::fromProcessDates(
            $applicationDate,
            FixedDates::april5(),
            FixedDates::april10(),
            FixedDates::april15(),
            FixedDates::april20(),
        ));

        self::assertSame($applicationDate, $trackedJob->timeline()->applicationDate());
        self::assertEquals(FixedDates::april5(), $trackedJob->timeline()->effectiveFollowUpDate());
        self::assertEquals(FixedDates::april10(), $trackedJob->timeline()->firstContactDate());
        self::assertEquals(FixedDates::april15(), $trackedJob->timeline()->preliminaryInterviewDate());
        self::assertEquals(FixedDates::april20(), $trackedJob->timeline()->secondInterviewDate());
        self::assertSame('2026-04-16T00:00:00+00:00', $trackedJob->timeline()->plannedFollowUpDate()?->format('c'));
    }

    public function testUpdateTimelineClearsPlannedFollowUpWhenApplicationDateIsNull(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());
        $trackedJob->updateTimeline(TrackedJobTimeline::fromProcessDates(FixedDates::april1(), null, null, null, null));

        self::assertNotNull($trackedJob->timeline()->plannedFollowUpDate());

        $trackedJob->updateTimeline(TrackedJobTimeline::fromProcessDates(null, null, null, null, null));

        self::assertNull($trackedJob->timeline()->applicationDate());
        self::assertNull($trackedJob->timeline()->plannedFollowUpDate());
    }

    public function testUpdateContactsNormalizesNames(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());

        $trackedJob->updateContacts(ContactName::fromNullable('  Jane HR  '), ContactName::fromNullable('   '));

        self::assertSame('Jane HR', $trackedJob->hrContactName()?->value());
        self::assertNull($trackedJob->businessContactName()?->value());
    }

    public function testUpdateRelevanceStoresValueAndAllowsNull(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());

        $trackedJob->updateRelevance(SubjectiveRelevance::fromInt(8));
        self::assertSame(8, $trackedJob->getSubjectiveRelevance());

        $trackedJob->updateRelevance(null);
        self::assertNull($trackedJob->getSubjectiveRelevance());
    }

    public function testTouchUpdatesUpdatedAt(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build()->getId());
        $property = new \ReflectionProperty($trackedJob, 'updatedAt');
        $oldValue = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $property->setValue($trackedJob, $oldValue);

        $trackedJob->touch();

        self::assertNotSame($oldValue, $trackedJob->getUpdatedAt());
        self::assertGreaterThan($oldValue, $trackedJob->getUpdatedAt());
    }

    public function testReconstituteRestoresPersistedStateWithoutRecomputingBusinessState(): void
    {
        $id = TrackedJobId::fromString('018f6d6f-0000-7000-8000-000000000004');
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
            $owner->getId(),
            CompanyName::fromNullable('  Acme  '),
            JobTitle::fromNullable('  Backend Engineer  '),
            ContractType::CDD,
            '  Paris  ',
            RemoteMode::HYBRID,
            '  60k  ',
            OfferUrl::fromNullable('  https://example.com/job  '),
            TrackedJobNotes::fromNullable('  Strong fit  '),
            $applicationDate,
            $plannedFollowUpDate,
            $effectiveFollowUpDate,
            $firstContactDate,
            $preliminaryInterviewDate,
            $secondInterviewDate,
            ContactName::fromNullable('  Jane HR  '),
            ContactName::fromNullable('   '),
            SubjectiveRelevance::fromInt(8),
            TrackedJobStatus::HIRED,
            $createdAt,
            $updatedAt,
        );

        self::assertSame($id, $trackedJob->getId());
        self::assertTrue($owner->getId()->equals($trackedJob->ownerId()));
        self::assertSame('Acme', $trackedJob->company()?->value());
        self::assertSame('Backend Engineer', $trackedJob->title()?->value());
        self::assertSame(ContractType::CDD, $trackedJob->getContractType());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertSame(RemoteMode::HYBRID, $trackedJob->getRemoteMode());
        self::assertSame('60k', $trackedJob->getRemuneration());
        self::assertSame('https://example.com/job', $trackedJob->offerUrl()?->value());
        self::assertSame('Strong fit', $trackedJob->notes()?->value());
        self::assertSame($applicationDate, $trackedJob->timeline()->applicationDate());
        self::assertSame($plannedFollowUpDate, $trackedJob->timeline()->plannedFollowUpDate());
        self::assertSame($effectiveFollowUpDate, $trackedJob->timeline()->effectiveFollowUpDate());
        self::assertSame($firstContactDate, $trackedJob->timeline()->firstContactDate());
        self::assertSame($preliminaryInterviewDate, $trackedJob->timeline()->preliminaryInterviewDate());
        self::assertSame($secondInterviewDate, $trackedJob->timeline()->secondInterviewDate());
        self::assertSame('Jane HR', $trackedJob->hrContactName()?->value());
        self::assertNull($trackedJob->businessContactName()?->value());
        self::assertSame(8, $trackedJob->getSubjectiveRelevance());
        self::assertSame(TrackedJobStatus::HIRED, $trackedJob->getStatus());
        self::assertSame($createdAt, $trackedJob->getCreatedAt());
        self::assertSame($updatedAt, $trackedJob->getUpdatedAt());
    }
}
