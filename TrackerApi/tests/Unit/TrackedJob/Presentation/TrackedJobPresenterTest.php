<?php

namespace App\Tests\Unit\TrackedJob\Presentation;

use App\TrackedJob\Presentation\TrackedJobPresenter;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class TrackedJobPresenterTest extends TestCase
{
    public function testPresentMapsTrackedJobToArray(): void
    {
        $trackedJob = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->build();

        $presented = (new TrackedJobPresenter())->present($trackedJob);

        self::assertSame($trackedJob->getId()->toRfc4122(), $presented['id']);
        self::assertSame('Acme', $presented['company']);
        self::assertSame('Backend Engineer', $presented['title']);
        self::assertSame('CDI', $presented['contractType']);
        self::assertSame('HYBRID', $presented['remoteMode']);
        self::assertSame(FixedDates::april1()->format(\DateTimeInterface::ATOM), $presented['applicationDate']);
        self::assertSame(FixedDates::april15()->format(\DateTimeInterface::ATOM), $presented['plannedFollowUpDate']);
        self::assertArrayHasKey('createdAt', $presented);
        self::assertArrayHasKey('updatedAt', $presented);
    }
}