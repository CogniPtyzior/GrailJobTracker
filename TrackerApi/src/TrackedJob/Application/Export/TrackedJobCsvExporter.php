<?php

namespace App\TrackedJob\Application\Export;

use App\TrackedJob\Domain\Entity\TrackedJob;

final class TrackedJobCsvExporter
{
    /**
     * @param list<TrackedJob> $items
     */
    public function export(array $items): string
    {
        $stream = fopen('php://temp', 'r+');

        if ($stream === false) {
            throw new \RuntimeException('Unable to create temporary CSV stream.');
        }

        fputcsv($stream, [
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
        ], ';', '"', '\\');

        foreach ($items as $item) {
            fputcsv($stream, [
                $item->getId()->toRfc4122(),
                $item->getCompany(),
                $item->getTitle(),
                $item->getStatus()->value,
                $item->getContractType()?->value,
                $item->getLocation(),
                $item->getRemoteMode()?->value,
                $item->getRemuneration(),
                $item->getOfferUrl(),
                $item->getApplicationDate()?->format(\DateTimeInterface::ATOM),
                $item->getPlannedFollowUpDate()?->format(\DateTimeInterface::ATOM),
                $item->getEffectiveFollowUpDate()?->format(\DateTimeInterface::ATOM),
                $item->getFirstContactDate()?->format(\DateTimeInterface::ATOM),
                $item->getPreliminaryInterviewDate()?->format(\DateTimeInterface::ATOM),
                $item->getSecondInterviewDate()?->format(\DateTimeInterface::ATOM),
                $item->getHrContactName(),
                $item->getBusinessContactName(),
                $item->getSubjectiveRelevance(),
                $item->getNotes(),
                $item->getCreatedAt()->format(\DateTimeInterface::ATOM),
                $item->getUpdatedAt()->format(\DateTimeInterface::ATOM),
            ], ';', '"', '\\');
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv === false ? '' : $csv;
    }
}
