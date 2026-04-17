<?php

namespace App\Tests\Unit\TrackedJob\Domain\Entity;

use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\Tests\Support\Builder\UserBuilder;
use PHPUnit\Framework\TestCase;

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

    public function testTextSettersTrimAndConvertBlankStringsToNull(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());
        $trackedJob->setCompany('  Acme  ');
        $trackedJob->setTitle('   ');
        $trackedJob->setLocation('  Paris  ');
        $trackedJob->setOfferUrl('   ');

        self::assertSame('Acme', $trackedJob->getCompany());
        self::assertNull($trackedJob->getTitle());
        self::assertSame('Paris', $trackedJob->getLocation());
        self::assertNull($trackedJob->getOfferUrl());
    }

    public function testTouchUpdatesUpdatedAt(): void
    {
        $trackedJob = new TrackedJob(UserBuilder::aUser()->build());
        $property = new \ReflectionProperty($trackedJob, 'updatedAt');
        $property->setAccessible(true);
        $oldValue = new \DateTimeImmutable('2020-01-01T00:00:00+00:00');
        $property->setValue($trackedJob, $oldValue);

        $trackedJob->touch();

        self::assertNotSame($oldValue, $trackedJob->getUpdatedAt());
        self::assertGreaterThan($oldValue, $trackedJob->getUpdatedAt());
    }
}