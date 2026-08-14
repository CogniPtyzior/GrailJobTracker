<?php

declare(strict_types=1);

/*
 * API Platform provider for admin access request listing.
 * It reads pagination and query filters from API context and delegates searching to the application use case.
 */

namespace App\AccessRequest\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\AccessRequest\Api\Mapper\AccessRequestApiMapper;
use App\AccessRequest\Api\Output\AccessRequestCollectionOutput;
use App\AccessRequest\Application\UseCase\SearchAccessRequests;

/** @implements ProviderInterface<AccessRequestCollectionOutput> */
final readonly class AdminAccessRequestCollectionProvider implements ProviderInterface
{
    public function __construct(
        private SearchAccessRequests $searchAccessRequests,
        private AccessRequestApiMapper $apiMapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AccessRequestCollectionOutput
    {
        $filters = $context['filters'] ?? [];
        $page = min(max((int) ($filters['page'] ?? 1), 1), PHP_INT_MAX);
        $pageSize = min(max((int) ($filters['pageSize'] ?? 10), 1), 100);
        $query = isset($filters['query']) && is_string($filters['query']) ? $filters['query'] : null;

        return $this->apiMapper->toCollectionOutput(
            $this->searchAccessRequests->handle($query, $page, $pageSize),
            $page,
            $pageSize,
        );
    }
}
