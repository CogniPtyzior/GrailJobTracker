<?php

declare(strict_types=1);

/*
 * API Platform input DTO for partial admin user updates.
 * It records submitted fields so omitted values can be distinguished from explicit null values.
 */

namespace App\Security\Api\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateAdminUserInput
{
    /** @var list<string> */
    private array $providedFields = [];

    #[Groups(['admin_user:update'])]
    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Groups(['admin_user:update'])]
    #[Assert\Length(max: 120)]
    public ?string $lastName = null;

    #[Groups(['admin_user:update'])]
    #[Assert\When(expression: 'this.has("isActive")', constraints: [new Assert\NotNull()])]
    public ?bool $isActive = null;

    #[Groups(['admin_user:update'])]
    #[Assert\When(expression: 'this.has("isAdmin")', constraints: [new Assert\NotNull()])]
    public ?bool $isAdmin = null;

    #[Groups(['admin_user:update'])]
    #[Assert\NotBlank(allowNull: true)]
    #[Assert\Length(min: 8)]
    #[Assert\Regex('/\d/', message: 'Password must contain at least one digit.')]
    #[Assert\Regex('/[.#&!]/', message: 'Password must contain at least one allowed special character: . # & !')]
    public ?string $password = null;

    /**
     * @param list<string> $fields
     */
    public function setProvidedFields(array $fields): void
    {
        $this->providedFields = $fields;
    }

    public function has(string $field): bool
    {
        return in_array($field, $this->providedFields, true);
    }

    /**
     * @return list<string>
     */
    public function providedFields(): array
    {
        return $this->providedFields;
    }
}
