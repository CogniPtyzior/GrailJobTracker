<?php

namespace App\Tests\Unit\TrackedJob\Presentation;

use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use App\TrackedJob\Presentation\Payload\TrackedJobPayload;
use PHPUnit\Framework\TestCase;

final class TrackedJobPayloadTest extends TestCase
{
    public function testToInputNormalizesScalarsEnumsAndDates(): void
    {
        $input = (new TrackedJobPayload(
            company: '  Acme  ',
            title: '  Backend Engineer  ',
            contractType: 'CDD',
            location: '  Paris  ',
            remoteMode: 'FULL',
            remuneration: '  60k  ',
            offerUrl: '  https://example.com/job  ',
            notes: '  Strong fit  ',
            applicationDate: '2026-04-01T09:00:00+00:00',
            effectiveFollowUpDate: '2026-04-10T09:00:00+00:00',
            firstContactDate: '2026-04-11T09:00:00+00:00',
            preliminaryInterviewDate: '2026-04-15T09:00:00+00:00',
            secondInterviewDate: '2026-04-20T09:00:00+00:00',
            hrContactName: '  Jane HR  ',
            businessContactName: '  Bob Manager  ',
            subjectiveRelevance: '9',
            status: 'WITHDRAWN',
        ))->toCommand();

        self::assertSame('Acme', $input->company?->value());
        self::assertSame('Backend Engineer', $input->title?->value());
        self::assertSame(ContractType::CDD, $input->contractType);
        self::assertSame('Paris', $input->location);
        self::assertSame(RemoteMode::FULL, $input->remoteMode);
        self::assertSame('60k', $input->remuneration);
        self::assertSame('https://example.com/job', $input->offerUrl?->value());
        self::assertSame('Strong fit', $input->notes?->value());
        self::assertSame('2026-04-01T09:00:00+00:00', $input->applicationDate?->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-04-10T09:00:00+00:00', $input->effectiveFollowUpDate?->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-04-11T09:00:00+00:00', $input->firstContactDate?->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-04-15T09:00:00+00:00', $input->preliminaryInterviewDate?->format(\DateTimeInterface::ATOM));
        self::assertSame('2026-04-20T09:00:00+00:00', $input->secondInterviewDate?->format(\DateTimeInterface::ATOM));
        self::assertSame('Jane HR', $input->hrContactName?->value());
        self::assertSame('Bob Manager', $input->businessContactName?->value());
        self::assertSame(9, $input->subjectiveRelevance);
        self::assertSame(TrackedJobStatus::WITHDRAWN, $input->status);
    }

    public function testToInputConvertsBlankValuesToNull(): void
    {
        $input = (new TrackedJobPayload(
            company: '   ',
            title: '   ',
            contractType: null,
            location: '   ',
            remoteMode: null,
            remuneration: '   ',
            offerUrl: '   ',
            notes: '   ',
            applicationDate: '   ',
            effectiveFollowUpDate: '   ',
            firstContactDate: '   ',
            preliminaryInterviewDate: '   ',
            secondInterviewDate: '   ',
            hrContactName: '   ',
            businessContactName: '   ',
            subjectiveRelevance: '',
            status: null,
        ))->toCommand();

        self::assertNull($input->company);
        self::assertNull($input->title);
        self::assertNull($input->contractType);
        self::assertNull($input->location);
        self::assertNull($input->remoteMode);
        self::assertNull($input->remuneration);
        self::assertNull($input->offerUrl);
        self::assertNull($input->notes);
        self::assertNull($input->applicationDate);
        self::assertNull($input->effectiveFollowUpDate);
        self::assertNull($input->firstContactDate);
        self::assertNull($input->preliminaryInterviewDate);
        self::assertNull($input->secondInterviewDate);
        self::assertNull($input->hrContactName);
        self::assertNull($input->businessContactName);
        self::assertNull($input->subjectiveRelevance);
        self::assertNull($input->status);
    }
}
