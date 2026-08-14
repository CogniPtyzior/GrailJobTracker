<?php

declare(strict_types=1);

/*
 * API Platform processor for tracked job deletion.
 * It preserves owner-scoped loading before delegating removal to the application use case.
 */

namespace App\TrackedJob\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\TrackedJob\Application\UseCase\DeleteTrackedJob;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use Throwable;

/** @implements ProcessorInterface<mixed, null> */
final readonly class DeleteTrackedJobProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private GetTrackedJob $getTrackedJob,
        private DeleteTrackedJob $deleteTrackedJob,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        try {
            $id = TrackedJobId::fromString((string) ($uriVariables['id'] ?? ''));
        } catch (Throwable) {
            throw new ApplicationNotFound('Tracked job not found.');
        }

        $trackedJob = $this->getTrackedJob->handle($id, $this->authenticatedUserResolver->requireUser());

        if ($trackedJob === null) {
            throw new ApplicationNotFound('Tracked job not found.');
        }

        $this->deleteTrackedJob->handle($trackedJob);

        return null;
    }
}