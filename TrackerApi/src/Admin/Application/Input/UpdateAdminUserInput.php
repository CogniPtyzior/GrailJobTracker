<?php

namespace App\Admin\Application\Input;

use App\Shared\Domain\ValueObject\PersonName;

/**
 * Application input object used to apply a partial admin user update.
 */
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
