<?php

namespace App\Tests\Unit\TrackedJob\Application;

use App\TrackedJob\Application\Command\TrackedJobCommand;
use App\TrackedJob\Application\Date\TrackedJobDateParser;
use App\TrackedJob\Application\Service\TrackedJobCommandApplier;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Domain\ValueObject\CompanyName;
use App\TrackedJob\Domain\ValueObject\ContactName;
use App\TrackedJob\Domain\ValueObject\JobTitle;
use App\TrackedJob\Domain\ValueObject\OfferUrl;
use App\TrackedJob\Domain\ValueObject\TrackedJobNotes;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Builder\UserBuilder;
use PHPUnit\Framework\TestCase;

final class TrackedJobCommandApplierTest extends TestCase
{
    private TrackedJobCommandApplier $applier;

    protected function setUp(): void
    {
        $this->applier = new TrackedJobCommandApplier();
    }

    public function testApplyHydratesCommandIntoTrackedJob(): void
    {
        $trackedJob = TrackedJob::openFor(UserBuilder::aUser()->build());

        $this->applier->apply($trackedJob, $this->fullCommand());

        self::assertSame('Acme', $trackedJob->company()?->value());
        self::assertSame('Backend Engineer', $trackedJob->title()?->value());
        self::assertSame(ContractType::CDD, $trackedJob->getContractType());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertSame(RemoteMode::FULL, $trackedJob->getRemoteMode());
        self::assertSame('60k', $trackedJob->getRemuneration());
        self::assertSame('https://example.com/job', $trackedJob->offerUrl()?->value());
        self::assertSame('Strong fit', $trackedJob->notes()?->value());
        self::assertSame('Jane HR', $trackedJob->hrContactName()?->value());
        self::assertSame('Bob Manager', $trackedJob->businessContactName()?->value());
        self::assertSame(9, $trackedJob->getSubjectiveRelevance());
        self::assertSame(TrackedJobStatus::SECOND_INTERVIEW, $trackedJob->getStatus());
    }

    public function testApplyNullValuesAndDefaultsContractType(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $this->applier->apply($trackedJob, new TrackedJobCommand());

        self::assertNull($trackedJob->company()?->value());
        self::assertNull($trackedJob->title()?->value());
        self::assertSame(ContractType::CDI, $trackedJob->getContractType());
        self::assertNull($trackedJob->getRemoteMode());
        self::assertNull($trackedJob->getLocation());
        self::assertNull($trackedJob->getRemuneration());
        self::assertNull($trackedJob->offerUrl()?->value());
        self::assertNull($trackedJob->notes()?->value());
        self::assertNull($trackedJob->hrContactName()?->value());
        self::assertNull($trackedJob->businessContactName()?->value());
        self::assertNull($trackedJob->getSubjectiveRelevance());
    }

    public function testApplyComputesPlannedFollowUpDateFromApplicationDate(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();

        $this->applier->apply($trackedJob, $this->minimalCommand());

        self::assertSame(
            '2026-04-16T00:00:00+00:00',
            $trackedJob->timeline()->plannedFollowUpDate()?->format(\DateTimeInterface::ATOM),
        );
    }

    public function testApplyRespectsExplicitFinalStatus(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()->build();
        $command = new TrackedJobCommand(
            company: CompanyName::fromNullable('Acme'),
            title: JobTitle::fromNullable('Backend Engineer'),
            applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
            status: TrackedJobStatus::WITHDRAWN,
        );

        $this->applier->apply($trackedJob, $command);

        self::assertSame(TrackedJobStatus::WITHDRAWN, $trackedJob->getStatus());
    }

    private function minimalCommand(): TrackedJobCommand
    {
        return new TrackedJobCommand(
            company: CompanyName::fromNullable('Acme'),
            title: JobTitle::fromNullable('Backend Engineer'),
            applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
        );
    }

    private function fullCommand(): TrackedJobCommand
    {
        return new TrackedJobCommand(
            company: CompanyName::fromNullable('Acme'),
            title: JobTitle::fromNullable('Backend Engineer'),
            contractType: ContractType::CDD,
            location: 'Paris',
            remoteMode: RemoteMode::FULL,
            remuneration: '60k',
            offerUrl: OfferUrl::fromNullable('https://example.com/job'),
            notes: TrackedJobNotes::fromNullable('Strong fit'),
            applicationDate: TrackedJobDateParser::parseNullable('2026-04-01T09:00:00+00:00'),
            effectiveFollowUpDate: TrackedJobDateParser::parseNullable('2026-04-10T09:00:00+00:00'),
            firstContactDate: TrackedJobDateParser::parseNullable('2026-04-11T09:00:00+00:00'),
            preliminaryInterviewDate: TrackedJobDateParser::parseNullable('2026-04-15T09:00:00+00:00'),
            secondInterviewDate: TrackedJobDateParser::parseNullable('2026-04-20T09:00:00+00:00'),
            hrContactName: ContactName::fromNullable('Jane HR'),
            businessContactName: ContactName::fromNullable('Bob Manager'),
            subjectiveRelevance: 9,
        );
    }
}
