<?php

namespace App\Admin\Application\Input;

/**
 * Application input object used to apply a partial admin user update.
 */
final readonly class UpdateAdminUserInput
{
    /**
     * @param list<string> $providedFields
     */
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
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
