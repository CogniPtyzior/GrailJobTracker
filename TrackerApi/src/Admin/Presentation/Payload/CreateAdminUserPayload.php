<?php

namespace App\Admin\Presentation\Payload;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the admin user creation payload contract.
 */
final class CreateAdminUserPayload
{
    public static function constraint(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
                'password' => AdminUserPasswordPayload::constraint(),
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'isActive' => new Assert\Optional([new Assert\Type('bool')]),
                'isAdmin' => new Assert\Optional([new Assert\Type('bool')]),
            ],
            allowMissingFields: false,
            allowExtraFields: false,
        );
    }
}
