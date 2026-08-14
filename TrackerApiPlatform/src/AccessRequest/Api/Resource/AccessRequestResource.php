<?php

declare(strict_types=1);

/*
 * API Platform resource exposing public access request operations.
 * It keeps API metadata outside the domain while preserving the frontend-compatible public endpoint.
 */

namespace App\AccessRequest\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\AccessRequest\Api\Input\CreateAccessRequestInput;
use App\AccessRequest\Api\Processor\CreateAccessRequestProcessor;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'AccessRequest',
    operations: [
        new Post(
            uriTemplate: '/access-requests',
            input: CreateAccessRequestInput::class,
            output: false,
            inputFormats: ['json' => ['application/json']],
            denormalizationContext: ['groups' => ['access_request:create']],
            read: false,
            processor: CreateAccessRequestProcessor::class,
            status: Response::HTTP_CREATED,
            name: 'access_request_create',
        ),
    ],
)]
final class AccessRequestResource
{
}
