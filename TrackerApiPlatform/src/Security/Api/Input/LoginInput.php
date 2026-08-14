<?php

declare(strict_types=1);

/*
 * API Platform input DTO for the frontend-compatible JSON login payload.
 * Validation remains intentionally minimal because Symfony Security performs authentication failure handling.
 */

namespace App\Security\Api\Input;

use Symfony\Component\Validator\Constraints as Assert;

final class LoginInput
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email = '';

    #[Assert\NotBlank]
    public string $password = '';
}
