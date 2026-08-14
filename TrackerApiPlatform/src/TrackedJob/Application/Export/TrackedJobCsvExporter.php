<?php

declare(strict_types=1);

/*
 * CSV exporter for tracked jobs.
 * It belongs to the application layer because export formatting is a use-case contract, not HTTP infrastructure.
 */

namespace App\TrackedJob\Application\Export;

use App\TrackedJob\Domain\Entity\TrackedJob;
use RuntimeException;

final class TrackedJobCsvExporter
{
    /** @param list<TrackedJob> $items */
    public function export(array $items): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new RuntimeException('Unable to create temporary CSV stream.');
        }

        fputcsv($stream, $this->headers(), ';', '"', '\\');

        foreach ($items as $item) {
            $timeline = $item->timeline();

            fputcsv($stream, [
                $item->getId()->toRfc4122(),
                $item->company()?->value(),
                $item->title()?->value(),
                $item->getStatus()->value,
                $item->getContractType()?->value,
                $item->getLocation(),
                $item->getRemoteMode()?->value,
                $item->getRemuneration(),
                $item->offerUrl()?->value(),
                $timeline->applicationDate()?->format(\DateTimeInterface::ATOM),
                $timeline->plannedFollowUpDate()?->format(\DateTimeInterface::ATOM),
                $timeline->effectiveFollowUpDate()?->format(\DateTimeInterface::ATOM),
                $timeline->firstContactDate()?->format(\DateTimeInterface::ATOM),
                $timeline->preliminaryInterviewDate()?->format(\DateTimeInterface::ATOM),
                $timeline->secondInterviewDate()?->format(\DateTimeInterface::ATOM),
                $item->hrContactName()?->value(),
                $item->businessContactName()?->value(),
                $item->getSubjectiveRelevance(),
                $item->notes()?->value(),
                $item->getCreatedAt()->format(\DateTimeInterface::ATOM),
                $item->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ], ';', '"', '\\');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? '' : $csv;
    }

    /** @return list<string> */
    private function headers(): array
    {
        return [
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
        ];
    }
}
