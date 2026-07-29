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

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(User $user, User $currentUser, array $payload): User
    {
        $bootstrapAdmin = $user->isBootstrapAdmin($this->adminBootstrapEmail);

        $user->setFirstName($payload['firstName'] ?? $user->getFirstName());
        $user->setLastName($payload['lastName'] ?? $user->getLastName());

        if ($this->canUpdateActiveFlag($user, $currentUser, $payload, $bootstrapAdmin)) {
            $user->setIsActive($payload['isActive']);
        }

        if (array_key_exists('isAdmin', $payload)) {
            $user->setRoles($bootstrapAdmin ? ['ROLE_ADMIN', 'ROLE_USER'] : $this->rolesFromPayload($payload));
        }

        if (isset($payload['password']) && is_string($payload['password'])) {
            $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload['password']));
        }

        $this->entityManager->flush();

        return $user;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function canUpdateActiveFlag(
        User $user,
        User $currentUser,
        array $payload,
        bool $bootstrapAdmin,
    ): bool {
        if (!array_key_exists('isActive', $payload)) {
            return false;
        }

        if ($bootstrapAdmin && $currentUser->getId()->equals($user->getId()) && $payload['isActive'] === false) {
            return false;
        }

        return !$bootstrapAdmin || $payload['isActive'] === true;
    }

    /**
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function rolesFromPayload(array $payload): array
    {
        return $payload['isAdmin'] ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER'];
    }
}
