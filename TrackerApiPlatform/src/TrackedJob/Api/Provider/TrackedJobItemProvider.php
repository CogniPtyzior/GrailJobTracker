<?php

declare(strict_types=1);

/*
 * API Platform provider for a single tracked job.
 * It loads through the application use case so owner scoping remains query-side.
 */

namespace App\TrackedJob\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Output\TrackedJobItemOutput;
use App\TrackedJob\Api\Security\TrackedJobVoter;
use App\TrackedJob\Application\UseCase\GetTrackedJob;
use App\TrackedJob\Domain\ValueObject\TrackedJobId;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

/** @implements ProviderInterface<TrackedJobItemOutput> */
final readonly class TrackedJobItemProvider implements ProviderInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private GetTrackedJob $getTrackedJob,
        private TrackedJobApiMapper $mapper,
        private AuthorizationCheckerInterface $authorizationChecker,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TrackedJobItemOutput
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

        if (!$this->authorizationChecker->isGranted(TrackedJobVoter::VIEW, $trackedJob)) {
            throw new AccessDeniedException('Access denied.');
        }

        return $this->mapper->toItemOutput($trackedJob);
    }
}
