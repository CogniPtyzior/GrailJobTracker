<?php

declare(strict_types=1);

/*
 * API Platform processor for tracked job CSV exports.
 * It returns a Symfony response deliberately because CSV is a file response, not a JSON projection.
 */

namespace App\TrackedJob\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\TrackedJob\Api\Input\ExportTrackedJobsInput;
use App\TrackedJob\Api\Mapper\TrackedJobExportInputMapper;
use App\TrackedJob\Application\UseCase\ExportTrackedJobsCsv;
use Symfony\Component\HttpFoundation\Response;
use Webmozart\Assert\Assert;

/** @implements ProcessorInterface<ExportTrackedJobsInput, Response> */
final readonly class ExportTrackedJobsCsvProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private TrackedJobExportInputMapper $inputMapper,
        private ExportTrackedJobsCsv $exportTrackedJobsCsv,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Response
    {
        Assert::isInstanceOf($data, ExportTrackedJobsInput::class);

        $csv = $this->exportTrackedJobsCsv->handle(
            $this->authenticatedUserResolver->requireUser(),
            $this->inputMapper->toApplicationInput($data),
        );

        return new Response($csv, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="tracked-jobs.csv"',
        ]);
    }
}
