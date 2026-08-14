<?php

declare(strict_types=1);

/*
 * API Platform processor for admin user updates.
 * It loads the target user through the application layer, then delegates mutation to the update use case.
 */

namespace App\Security\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Security\Api\Input\UpdateAdminUserInput;
use App\Security\Api\Mapper\AdminUserApiMapper;
use App\Security\Api\Mapper\AdminUserInputMapper;
use App\Security\Api\Output\AdminUserItemOutput;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Security\Application\UseCase\GetAdminUser;
use App\Security\Application\UseCase\UpdateAdminUser;
use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Shared\Application\Exception\ApplicationNotFound;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use Throwable;

/** @implements ProcessorInterface<UpdateAdminUserInput, AdminUserItemOutput> */
final readonly class UpdateAdminUserProcessor implements ProcessorInterface
{
    public function __construct(
        private AuthenticatedUserResolver $authenticatedUserResolver,
        private GetAdminUser $getAdminUser,
        private UpdateAdminUser $updateAdminUser,
        private AdminUserInputMapper $inputMapper,
        private AdminUserApiMapper $apiMapper,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): AdminUserItemOutput
    {
        if (!$data instanceof UpdateAdminUserInput) {
            throw new InvalidApplicationCommand('Invalid admin user update payload.');
        }

        $user = $this->loadUser($uriVariables['id'] ?? null);
        $updated = $this->updateAdminUser->handle(
            $user,
            $this->authenticatedUserResolver->requireUser(),
            $this->inputMapper->toUpdateInput($data),
        );

        return $this->apiMapper->toItemOutput($updated);
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
