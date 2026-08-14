<?php

declare(strict_types=1);

/*
 * API Platform processor for admin user deletion.
 * It keeps HTTP concerns local and delegates deletion guards and persistence to application services.
 */

namespace App\Security\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Security\Application\UseCase\DeleteAdminUser;
use App\Security\Application\UseCase\GetAdminUser;
use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Shared\Application\Exception\ApplicationNotFound;
use Throwable;

/** @implements ProcessorInterface<null, null> */
final readonly class DeleteAdminUserProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private GetAdminUser $getAdminUser,
        private DeleteAdminUser $deleteAdminUser,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): null
    {
        $this->deleteAdminUser->handle(
            $this->loadUser($uriVariables['id'] ?? null),
            $this->authenticatedUserResolver->requireUser(),
        );

        return null;
    }

    private function loadUser(mixed $id): User
    {
        try {
            $userId = UserId::fromString((string) $id);
        } catch (Throwable) {
            throw new ApplicationNotFound('User not found.');
        }

        return $this->getAdminUser->handle($userId) ?? throw new ApplicationNotFound('User not found.');
    }
}
