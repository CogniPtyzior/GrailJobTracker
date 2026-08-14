<?php

declare(strict_types=1);

/*
 * API Platform input DTO for public access request creation.
 * Symfony Validator owns transport-level constraints before values enter the application use case.
 */

namespace App\AccessRequest\Api\Input;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateAccessRequestInput
{
    #[Groups(['access_request:create'])]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Email]
    #[Assert\Length(max: 180)]
    public string $email = '';

    #[Groups(['access_request:create'])]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(max: 255, normalizer: 'trim')]
    public string $companyName = '';

    #[Groups(['access_request:create'])]
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Length(min: 20, max: 5000, normalizer: 'trim')]
    public string $reason = '';

    #[Groups(['access_request:create'])]
    #[Assert\Length(max: 120)]
    public ?string $firstName = null;

    #[Groups(['access_request:create'])]
    #[Assert\Length(max: 120)]
    public ?string $lastName = null;
}
