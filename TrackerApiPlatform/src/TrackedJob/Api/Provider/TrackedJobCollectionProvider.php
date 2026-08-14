<?php

declare(strict_types=1);

/*
 * API Platform provider for tracked job collections.
 * It parses query parameters, delegates owner-filtered search to the application layer and maps the result to API DTOs.
 */

namespace App\TrackedJob\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\TrackedJob\Api\Mapper\TrackedJobApiMapper;
use App\TrackedJob\Api\Output\TrackedJobCollectionOutput;
use App\TrackedJob\Application\UseCase\SearchTrackedJobs;
use App\TrackedJob\Domain\Enum\ContractType;
use App\TrackedJob\Domain\Enum\RemoteMode;
use App\TrackedJob\Domain\Enum\TrackedJobStatus;
use Symfony\Component\HttpFoundation\RequestStack;

/** @implements ProviderInterface<TrackedJobCollectionOutput> */
final readonly class TrackedJobCollectionProvider implements ProviderInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private SearchTrackedJobs $searchTrackedJobs,
        private TrackedJobApiMapper $mapper,
        private RequestStack $requestStack,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TrackedJobCollectionOutput
    {
        $request = $this->requestStack->getCurrentRequest();
        $query = $request?->query;
        $page = max((int) ($query?->get('page', 1) ?? 1), 1);
        $pageSize = min(max((int) ($query?->get('pageSize', 10) ?? 10), 1), 100);
        $statusRaw = trim((string) ($query?->get('status', '') ?? ''));
        $contractTypeRaw = trim((string) ($query?->get('contractType', '') ?? ''));
        $remoteModeRaw = trim((string) ($query?->get('remoteMode', '') ?? ''));
        $status = TrackedJobStatus::tryFrom($statusRaw);
        $contractType = ContractType::tryFrom($contractTypeRaw);
        $remoteMode = RemoteMode::tryFrom($remoteModeRaw);

        $result = $this->searchTrackedJobs->handle($this->authenticatedUserResolver->requireUser(), [
            'search' => $query?->get('search'),
            'company' => $query?->get('company'),
            'status' => $status,
            'contractType' => $contractType,
            'remoteMode' => $remoteMode,
            'statusInvalid' => $statusRaw !== '' && $status === null,
            'contractTypeInvalid' => $contractTypeRaw !== '' && $contractType === null,
            'remoteModeInvalid' => $remoteModeRaw !== '' && $remoteMode === null,
        ], $page, $pageSize);

        return $this->mapper->toCollectionOutput($result, $page, $pageSize);
    }
}
