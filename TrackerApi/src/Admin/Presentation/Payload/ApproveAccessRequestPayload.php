<?php

namespace App\Admin\Presentation\Payload;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the payload contract used when an admin approves an access request.
 */
final class ApproveAccessRequestPayload
{
    public static function constraint(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'password' => AdminUserPasswordPayload::constraint(),
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
            ],
            allowMissingFields: false,
            allowExtraFields: false,
        );
    }
}
