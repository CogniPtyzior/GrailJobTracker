<?php

declare(strict_types=1);

/*
 * API Platform provider for admin user listing.
 * It translates query filters into an application search use case and returns the legacy pagination envelope.
 */

namespace App\Security\Api\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Security\Api\Mapper\AdminUserApiMapper;
use App\Security\Api\Output\AdminUserCollectionOutput;
use App\Security\Application\UseCase\SearchUsers;

/** @implements ProviderInterface<AdminUserCollectionOutput> */
final readonly class AdminUserCollectionProvider implements ProviderInterface
{
    public function __construct(
        private SearchUsers $searchUsers,
        private AdminUserApiMapper $apiMapper,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): AdminUserCollectionOutput
    {
        $filters = $context['filters'] ?? [];
        $page = min(max((int) ($filters['page'] ?? 1), 1), PHP_INT_MAX);
        $pageSize = min(max((int) ($filters['pageSize'] ?? 10), 1), 100);
        $query = isset($filters['query']) && is_string($filters['query']) ? $filters['query'] : null;
        $isActive = match ($filters['isActive'] ?? null) {
            'true' => true,
            'false' => false,
            default => null,
        };

        return $this->apiMapper->toCollectionOutput(
            $this->searchUsers->handle($isActive, $query, $page, $pageSize),
            $page,
            $pageSize,
        );
    }
}
