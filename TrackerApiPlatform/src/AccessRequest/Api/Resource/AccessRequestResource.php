<?php

declare(strict_types=1);

/*
 * API Platform resource exposing public and admin access request operations.
 * It keeps API metadata outside the domain while preserving frontend-compatible endpoint contracts.
 */

namespace App\AccessRequest\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\AccessRequest\Api\Input\ApproveAccessRequestInput;
use App\AccessRequest\Api\Input\CreateAccessRequestInput;
use App\AccessRequest\Api\Output\AccessRequestCollectionOutput;
use App\AccessRequest\Api\Output\ApprovedAccessRequestOutput;
use App\AccessRequest\Api\Processor\ApproveAccessRequestProcessor;
use App\AccessRequest\Api\Processor\CreateAccessRequestProcessor;
use App\AccessRequest\Api\Processor\DeleteAccessRequestProcessor;
use App\AccessRequest\Api\Provider\AdminAccessRequestCollectionProvider;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'AccessRequest',
    operations: [
        new Get(
            uriTemplate: '/admin/access-requests',
            output: AccessRequestCollectionOutput::class,
            normalizationContext: ['groups' => ['access_request:list', 'access_request:read']],
            provider: AdminAccessRequestCollectionProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            name: 'admin_access_request_list',
        ),
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
        new Post(
            uriTemplate: '/admin/access-requests/{id}/approve',
            input: ApproveAccessRequestInput::class,
            output: ApprovedAccessRequestOutput::class,
            normalizationContext: ['groups' => ['access_request:approved']],
            denormalizationContext: ['groups' => ['access_request:approve']],
            read: false,
            processor: ApproveAccessRequestProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            status: Response::HTTP_OK,
            name: 'admin_access_request_approve',
        ),
        new Delete(
            uriTemplate: '/admin/access-requests/{id}',
            output: false,
            read: false,
            processor: DeleteAccessRequestProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            status: Response::HTTP_NO_CONTENT,
            name: 'admin_access_request_delete',
        ),
    ],
)]
final class AccessRequestResource
{
}
