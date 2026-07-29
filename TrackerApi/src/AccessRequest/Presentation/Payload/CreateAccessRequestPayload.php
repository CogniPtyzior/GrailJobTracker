<?php

namespace App\AccessRequest\Presentation\Payload;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Defines the public access request payload contract used by the HTTP controller.
 */
final class CreateAccessRequestPayload
{
    public static function constraint(): Assert\Collection
    {
        return new Assert\Collection(
            fields: [
                'email' => [new Assert\NotBlank(), new Assert\Email(), new Assert\Length(max: 180)],
                'companyName' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 255)],
                'reason' => [new Assert\NotBlank(), new Assert\Type('string'), new Assert\Length(max: 5000)],
                'firstName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
                'lastName' => new Assert\Optional([new Assert\Type('string'), new Assert\Length(max: 120)]),
            ],
            allowMissingFields: false,
            allowExtraFields: false,
        );
    }
}
