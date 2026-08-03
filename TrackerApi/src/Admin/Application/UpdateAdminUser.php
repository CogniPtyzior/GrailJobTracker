<?php

namespace App\Admin\Application;

use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that applies an admin user update while preserving bootstrap admin guards.
 */
final class UpdateAdminUser
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
        private readonly string $adminBootstrapEmail,
    ) {
    }

    public function handle(User $user, User $currentUser, UpdateAdminUserInput $payload): User
    {
        $bootstrapAdmin = $user->isBootstrapAdmin($this->adminBootstrapEmail);

        if ($payload->has('firstName')) {
            $user->setFirstName($payload->firstName);
        }

        if ($payload->has('lastName')) {
            $user->setLastName($payload->lastName);
        }

        if ($this->canUpdateActiveFlag($user, $currentUser, $payload, $bootstrapAdmin)) {
            $user->setIsActive($payload->isActive ?? false);
        }

        if ($payload->has('isAdmin')) {
            $user->setRoles($bootstrapAdmin ? ['ROLE_ADMIN', 'ROLE_USER'] : $this->rolesFromPayload($payload));
        }

        if ($payload->has('password') && $payload->password !== null) {
            $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload->password));
        }

        $this->entityManager->flush();

        return $user;
    }

    private function canUpdateActiveFlag(
        User $user,
        User $currentUser,
        UpdateAdminUserInput $payload,
        bool $bootstrapAdmin,
    ): bool {
        if (!$payload->has('isActive')) {
            return false;
        }

        if ($bootstrapAdmin && $currentUser->getId()->equals($user->getId()) && $payload->isActive === false) {
            return false;
        }

        return !$bootstrapAdmin || $payload->isActive === true;
    }

    /**
     * @return list<string>
     */
    private function rolesFromPayload(UpdateAdminUserInput $payload): array
    {
        return $payload->isAdmin ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
    }
}
