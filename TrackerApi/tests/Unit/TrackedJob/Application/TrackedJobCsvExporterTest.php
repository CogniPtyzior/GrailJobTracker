<?php

namespace App\Tests\Unit\TrackedJob\Application;

use App\TrackedJob\Application\Export\TrackedJobCsvExporter;
use App\Tests\Support\Builder\TrackedJobBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class TrackedJobCsvExporterTest extends TestCase
{
    public function testExportIncludesExpectedHeader(): void
    {
        $csv = (new TrackedJobCsvExporter())->export([]);
        $lines = preg_split('/\r\n|\n|\r/', trim($csv));

        self::assertNotFalse($lines);
        self::assertSame([
            'Id',
            'Company',
            'Title',
            'Status',
            'ContractType',
            'Location',
            'RemoteMode',
            'Remuneration',
            'OfferUrl',
            'ApplicationDate',
            'PlannedFollowUpDate',
            'EffectiveFollowUpDate',
            'FirstContactDate',
            'PreliminaryInterviewDate',
            'SecondInterviewDate',
            'HrContactName',
            'BusinessContactName',
            'SubjectiveRelevance',
            'Notes',
            'CreatedAt',
            'UpdatedAt',
        ], str_getcsv($lines[0], ';', '"', '\\'));
    }

    public function testExportSerializesRowsAndKeepsEmptyOptionalFields(): void
    {
        $job = TrackedJobBuilder::aTrackedJob()
            ->withApplicationDate(FixedDates::april1())
            ->withPlannedFollowUpDate(FixedDates::april15())
            ->build();

        $csv = (new TrackedJobCsvExporter())->export([$job]);
        $lines = preg_split('/\r\n|\n|\r/', trim($csv));

        self::assertNotFalse($lines);
        $row = str_getcsv($lines[1], ';', '"', '\\');

        self::assertSame($job->getId()->toRfc4122(), $row[0]);
        self::assertSame('Acme', $row[1]);
        self::assertSame('Backend Engineer', $row[2]);
        self::assertSame($job->getStatus()->value, $row[3]);
        self::assertSame(FixedDates::april1()->format(\DateTimeInterface::ATOM), $row[9]);
        self::assertSame(FixedDates::april15()->format(\DateTimeInterface::ATOM), $row[10]);
        self::assertSame('', $row[11]);
        self::assertSame('', $row[12]);
        self::assertSame('', $row[13]);
        self::assertSame('', $row[14]);
    }
}