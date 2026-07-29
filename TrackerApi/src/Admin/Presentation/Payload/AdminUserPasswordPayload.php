<?php

namespace App\Admin\Presentation\Payload;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Shared password rules for admin-created users and access request approvals.
 */
final class AdminUserPasswordPayload
{
    public static function constraint(): Assert\Sequentially
    {
        return new Assert\Sequentially([
            new Assert\NotBlank(),
            new Assert\Length(min: 8),
            new Assert\Regex('/\d/', 'Password must contain at least one digit.'),
            new Assert\Regex('/[.#&!]/', 'Password must contain at least one allowed special character: . # & !'),
        ]);
    }
}
