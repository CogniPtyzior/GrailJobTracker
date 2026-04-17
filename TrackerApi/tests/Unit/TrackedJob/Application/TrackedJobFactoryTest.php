<?php

namespace App\Tests\Unit\TrackedJob\Application;

use App\TrackedJob\Application\TrackedJobFactory;
use App\TrackedJob\Application\TrackedJobStatusResolver;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Payload\TrackedJobPayloads;
use PHPUnit\Framework\TestCase;

final class TrackedJobFactoryTest extends TestCase
{
    private TrackedJobFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TrackedJobFactory(new TrackedJobStatusResolver());
    }

    public function testCreateHydratesPayloadIntoTrackedJob(): void
    {
        $trackedJob = $this->factory->create(UserBuilder::aUser()->build(), TrackedJobPayloads::full());

        self::assertSame('Acme', $trackedJob->getCompany());
        self::assertSame('Backend Engineer', $trackedJob->getTitle());
        self::assertSame(ContractType::CDD, $trackedJob->getContractType());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertSame(RemoteMode::FULL, $trackedJob->getRemoteMode());
        self::assertSame('60k', $trackedJob->getRemuneration());
        self::assertSame('https://example.com/job', $trackedJob->getOfferUrl());
        self::assertSame('Strong fit', $trackedJob->getNotes());
        self::assertSame('Jane HR', $trackedJob->getHrContactName());
        self::assertSame('Bob Manager', $trackedJob->getBusinessContactName());
        self::assertSame(9, $trackedJob->getSubjectiveRelevance());
        self::assertSame(TrackedJobStatus::SECOND_INTERVIEW, $trackedJob->getStatus());
    }

    public function testHydrateConvertsBlankStringsAndInvalidEnums(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $this->factory->hydrate($trackedJob, TrackedJobPayloads::withInvalidEnums());

        self::assertNull($trackedJob->getCompany());
        self::assertNull($trackedJob->getTitle());
        self::assertSame(ContractType::CDI, $trackedJob->getContractType());
        self::assertNull($trackedJob->getRemoteMode());
        self::assertNull($trackedJob->getLocation());
        self::assertNull($trackedJob->getRemuneration());
        self::assertNull($trackedJob->getOfferUrl());
        self::assertNull($trackedJob->getNotes());
        self::assertNull($trackedJob->getHrContactName());
        self::assertNull($trackedJob->getBusinessContactName());
        self::assertNull($trackedJob->getSubjectiveRelevance());
    }

    public function testHydrateComputesPlannedFollowUpDateFromApplicationDate(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $this->factory->hydrate($trackedJob, TrackedJobPayloads::minimal());

        self::assertSame('2026-04-16T00:00:00+00:00', $trackedJob->getPlannedFollowUpDate()?->format(\DateTimeInterface::ATOM));
    }

    public function testHydrateRespectsExplicitFinalStatus(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();
        $payload = TrackedJobPayloads::minimal();
        $payload['status'] = TrackedJobStatus::WITHDRAWN->value;

        $this->factory->hydrate($trackedJob, $payload);

        self::assertSame(TrackedJobStatus::WITHDRAWN, $trackedJob->getStatus());
    }
}