<?php

declare(strict_types=1);

/*
 * Application input for partial admin user updates.
 * The provided field list preserves legacy partial-update semantics without coupling the use case to HTTP.
 */

namespace App\Security\Application\Input;

use App\Shared\Domain\ValueObject\PersonName;

final readonly class UpdateAdminUserInput
{
    /**
     * @param list<string> $providedFields
     */
    public function __construct(
        public ?PersonName $firstName,
        public ?PersonName $lastName,
        public ?bool $isActive,
        public ?bool $isAdmin,
        public ?string $password,
        private array $providedFields,
    ) {
    }

    public function has(string $field): bool
    {
        return in_array($field, $this->providedFields, true);
    }
}
