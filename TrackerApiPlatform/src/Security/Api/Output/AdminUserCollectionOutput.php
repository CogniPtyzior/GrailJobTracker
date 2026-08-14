<?php

declare(strict_types=1);

/*
 * API output DTO for admin user collections.
 * The envelope intentionally mirrors the existing frontend pagination contract.
 */

namespace App\Security\Api\Output;

use Symfony\Component\Serializer\Attribute\Groups;

final readonly class AdminUserCollectionOutput
{
    /**
     * @param list<AdminUserOutput> $items
     */
    public function __construct(
        #[Groups(['admin_user:list'])]
        public array $items,
        #[Groups(['admin_user:list'])]
        public int $page,
        #[Groups(['admin_user:list'])]
        public int $pageSize,
        #[Groups(['admin_user:list'])]
        public int $total,
    ) {
    }
}
