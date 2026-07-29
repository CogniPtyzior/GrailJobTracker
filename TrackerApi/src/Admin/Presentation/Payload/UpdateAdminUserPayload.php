<?php

namespace App\Admin\Presentation\Payload;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the partial admin user update payload contract.
 */
final class UpdateAdminUserPayload
{
    public static function constraint(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'isActive' => new Assert\Optional([new Assert\Type('bool')]),
                'isAdmin' => new Assert\Optional([new Assert\Type('bool')]),
                'password' => new Assert\Optional([AdminUserPasswordPayload::constraint()]),
            ],
            allowMissingFields: true,
            allowExtraFields: false,
        );
    }
}
