<?php

declare(strict_types=1);

/*
 * API Platform input DTO for admin user creation.
 * Symfony Validator owns transport-level constraints while application and domain layers enforce business rules.
 */

namespace App\Security\Api\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateAdminUserInput
{
    #[Groups(['admin_user:create'])]
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Groups(['admin_user:create'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex('/\d/', message: 'Password must contain at least one digit.')]
    #[Assert\Regex('/[.#&!]/', message: 'Password must contain at least one allowed special character: . # & !')]
    public string $password = '';

    #[Groups(['admin_user:create'])]
    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Groups(['admin_user:create'])]
    #[Assert\Length(max: 120)]
    public ?string $lastName = null;

    #[Groups(['admin_user:create'])]
    public bool $isActive = true;

    #[Groups(['admin_user:create'])]
    public bool $isAdmin = false;
}
