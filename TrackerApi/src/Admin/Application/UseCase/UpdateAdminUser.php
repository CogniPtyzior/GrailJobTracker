<?php

namespace App\Admin\Application\UseCase;

use App\Admin\Application\Input\UpdateAdminUserInput;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that applies an admin user update while preserving bootstrap admin guards.
 */
final class UpdateAdminUser
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserRepositoryInterface $userRepository,
        private readonly string $adminBootstrapEmail,
    ) {
    }

    public function handle(User $user, User $currentUser, UpdateAdminUserInput $payload): User
    {
        $bootstrapAdmin = $user->isBootstrapAdmin($this->adminBootstrapEmail);

        if ($payload->has('firstName')) {
            $user->updateProfile($payload->firstName, $user->lastName());
        }

        if ($payload->has('lastName')) {
            $user->updateProfile($user->firstName(), $payload->lastName);
        }

        if ($this->canUpdateActiveFlag($user, $currentUser, $payload, $bootstrapAdmin)) {
            ($payload->isActive ?? false) ? $user->activate() : $user->deactivate();
        }

        if ($payload->has('isAdmin')) {
            $bootstrapAdmin ? $user->grantAdmin() : $user->updateAdminRole((bool) $payload->isAdmin);
        }

        if ($payload->has('password') && $payload->password !== null) {
            $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload->password));
        }

        $this->userRepository->save($user);
        $this->userRepository->flush();

        return $user;
    }

    private function canUpdateActiveFlag(User $user, User $currentUser, UpdateAdminUserInput $payload, bool $bootstrapAdmin): bool
    {
        if (!$payload->has('isActive')) {
            return false;
        }

        if ($bootstrapAdmin && $currentUser->getId()->equals($user->getId()) && $payload->isActive === false) {
            return false;
        }

        return !$bootstrapAdmin || $payload->isActive === true;
    }
}
