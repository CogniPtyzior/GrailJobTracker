<?php

declare(strict_types=1);

/*
 * API Platform processor for tracked job creation.
 * It keeps HTTP orchestration thin and delegates business behavior to the application use case.
 */

namespace App\TrackedJob\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use App\TrackedJob\Api\Input\CreateTrackedJobInput;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Mapper\TrackedJobInputMapper;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Application\UseCase\CreateTrackedJob;

/** @implements ProcessorInterface<CreateTrackedJobInput, TrackedJobItemOutput> */
final readonly class CreateTrackedJobProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private TrackedJobInputMapper $inputMapper,
        private CreateTrackedJob $createTrackedJob,
        private TrackedJobApiMapper $apiMapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TrackedJobItemOutput
    {
        if (!$data instanceof CreateTrackedJobInput) {
            throw new InvalidApplicationCommand('Invalid tracked job create payload.');
        }

        $trackedJob = $this->createTrackedJob->handle(
            $this->authenticatedUserResolver->requireUser(),
            $this->inputMapper->toCommand($data),
        );

        return $this->apiMapper->toItemOutput($trackedJob);
    }
}