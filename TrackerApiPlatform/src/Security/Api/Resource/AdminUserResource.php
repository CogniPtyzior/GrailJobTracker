<?php

declare(strict_types=1);

/*
 * API Platform resource exposing admin user management operations.
 * It is an inbound adapter over the security context and keeps the frontend-compatible contract explicit.
 */

namespace App\Security\Api\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\Put;
use App\Security\Api\Input\CreateAdminUserInput;
use App\Security\Api\Input\UpdateAdminUserInput;
use App\Security\Api\Output\AdminUserCollectionOutput;
use App\Security\Api\Output\AdminUserItemOutput;
use App\Security\Api\Processor\CreateAdminUserProcessor;
use App\Security\Api\Processor\DeleteAdminUserProcessor;
use App\Security\Api\Processor\UpdateAdminUserProcessor;
use App\Security\Api\Provider\AdminUserCollectionProvider;
use Symfony\Component\HttpFoundation\Response;

#[ApiResource(
    shortName: 'AdminUser',
    operations: [
        new Get(
            uriTemplate: '/admin/users',
            output: AdminUserCollectionOutput::class,
            normalizationContext: ['groups' => ['admin_user:list', 'admin_user:read']],
            provider: AdminUserCollectionProvider::class,
            security: "is_granted('ROLE_ADMIN')",
            name: 'admin_user_list',
        ),
        new Post(
            uriTemplate: '/admin/users',
            input: CreateAdminUserInput::class,
            output: AdminUserItemOutput::class,
            inputFormats: ['json' => ['application/json']],
            normalizationContext: ['groups' => ['admin_user:read']],
            denormalizationContext: ['groups' => ['admin_user:create']],
            read: false,
            processor: CreateAdminUserProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            status: Response::HTTP_CREATED,
            name: 'admin_user_create',
        ),
        new Put(
            uriTemplate: '/admin/users/{id}',
            input: UpdateAdminUserInput::class,
            output: AdminUserItemOutput::class,
            inputFormats: ['json' => ['application/json']],
            normalizationContext: ['groups' => ['admin_user:read']],
            denormalizationContext: ['groups' => ['admin_user:update']],
            read: false,
            processor: UpdateAdminUserProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            status: Response::HTTP_OK,
            name: 'admin_user_update',
        ),
        new Delete(
            uriTemplate: '/admin/users/{id}',
            output: false,
            read: false,
            processor: DeleteAdminUserProcessor::class,
            security: "is_granted('ROLE_ADMIN')",
            status: Response::HTTP_NO_CONTENT,
            name: 'admin_user_delete',
        ),
    ],
)]
final class AdminUserResource
{
}
