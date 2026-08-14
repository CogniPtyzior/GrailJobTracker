<?php

declare(strict_types=1);

/*
 * API Platform processor for tracked job updates.
 * The existing aggregate is loaded through the owner-scoped application query before mutation.
 */

namespace App\TrackedJob\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use App\TrackedJob\Api\Input\UpdateTrackedJobInput;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Mapper\TrackedJobInputMapper;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Security\TrackedJobVoter;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Application\UseCase\UpdateTrackedJob;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/** @implements ProcessorInterface<UpdateTrackedJobInput, TrackedJobItemOutput> */
final readonly class UpdateTrackedJobProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private GetTrackedJob $getTrackedJob,
        private TrackedJobInputMapper $inputMapper,
        private UpdateTrackedJob $updateTrackedJob,
        private TrackedJobApiMapper $apiMapper,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): TrackedJobItemOutput
    {
        if (!$data instanceof UpdateTrackedJobInput) {
            throw new InvalidApplicationCommand('Invalid tracked job update payload.');
        }

        $trackedJob = $this->loadTrackedJob($uriVariables);

        if (!$this->authorizationChecker->isGranted(TrackedJobVoter::UPDATE, $trackedJob)) {
            throw new AccessDeniedException('Access denied.');
        }
        $updated = $this->updateTrackedJob->handle($trackedJob, $this->inputMapper->toCommand($data));

        return $this->apiMapper->toItemOutput($updated);
    }

    /** @param array<string, mixed> $uriVariables */
    private function loadTrackedJob(array $uriVariables): TrackedJob
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

        return $trackedJob;
    }
}