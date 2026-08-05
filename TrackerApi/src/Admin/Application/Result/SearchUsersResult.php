<?php

namespace App\Admin\Application\Result;

use App\Security\Domain\Entity\User;

/**
 * Application result returned by the admin user search use case.
 */
final readonly class SearchUsersResult
{
    /**
     * @param list<User> $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {
    }
}