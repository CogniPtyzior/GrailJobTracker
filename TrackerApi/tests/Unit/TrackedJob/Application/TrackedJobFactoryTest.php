<?php

namespace App\Tests\Unit\TrackedJob\Application;

use App\TrackedJob\Application\Date\TrackedJobDateParser;
use App\TrackedJob\Application\Factory\TrackedJobFactory;
use App\TrackedJob\Application\Input\TrackedJobInput;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Builder\UserBuilder;
use PHPUnit\Framework\TestCase;

final class TrackedJobFactoryTest extends TestCase
{
    private TrackedJobFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TrackedJobFactory();
    }

    public function testCreateHydratesInputIntoTrackedJob(): void
    {
        $trackedJob = $this->factory->create(UserBuilder::aUser()->build(), $this->fullInput());

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

    public function testHydrateAppliesNullValuesAndDefaultsContractType(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $this->factory->hydrate($trackedJob, new TrackedJobInput());

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

        $this->factory->hydrate($trackedJob, $this->minimalInput());

        self::assertSame(
            '2026-04-16T00:00:00+00:00',
            $trackedJob->getPlannedFollowUpDate()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function testHydrateRespectsExplicitFinalStatus(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();
        $input = new TrackedJobInput(
            company: 'Acme',
            title: 'Backend Engineer',
            applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
            status: TrackedJobStatus::WITHDRAWN,
        );

        $this->factory->hydrate($trackedJob, $input);

        self::assertSame(TrackedJobStatus::WITHDRAWN, $trackedJob->getStatus());
    }

    private function minimalInput(): TrackedJobInput
    {
        return new TrackedJobInput(
            company: 'Acme',
            title: 'Backend Engineer',
            applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
        );
    }

    private function fullInput(): TrackedJobInput
    {
        return new TrackedJobInput(
            company: 'Acme',
            title: 'Backend Engineer',
            contractType: ContractType::CDD,
            location: 'Paris',
            remoteMode: RemoteMode::FULL,
            remuneration: '60k',
            offerUrl: 'https://example.com/job',
            notes: 'Strong fit',
            applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
            effectiveFollowUpDate: TrackedJobDateParser::parseNullable('2026-04-10T09:00:00+00:00'),
            firstContactDate: TrackedJobDateParser::parseNullable('2026-04-11T09:00:00+00:00'),
            preliminaryInterviewDate: TrackedJobDateParser::parseNullable('2026-04-15T09:00:00+00:00'),
            secondInterviewDate: TrackedJobDateParser::parseNullable('2026-04-20T09:00:00+00:00'),
            hrContactName: 'Jane HR',
            businessContactName: 'Bob Manager',
            subjectiveRelevance: 9,
        );
    }
}