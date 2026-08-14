<?php

declare(strict_types=1);

/*
 * API Platform input DTO for admin access request approval.
 * It preserves the frontend payload while Symfony Validator checks password and optional profile fields.
 */

namespace App\AccessRequest\Api\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class ApproveAccessRequestInput
{
    #[Groups(['access_request:approve'])]
    #[Assert\NotBlank]
    #[Assert\Length(min: 8)]
    #[Assert\Regex('/\d/', message: 'Password must contain at least one digit.')]
    #[Assert\Regex('/[.#&!]/', message: 'Password must contain at least one allowed special character: . # & !')]
    public string $password = '';

    #[Groups(['access_request:approve'])]
    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Groups(['access_request:approve'])]
    #[Assert\Length(max: 120)]
    public ?string $lastName = null;
}
