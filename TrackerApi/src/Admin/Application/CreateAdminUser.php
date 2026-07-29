<?php

namespace App\Admin\Application;

use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that creates an admin-managed user account.
 */
final class CreateAdminUser
{
    public function __construct(
        private readonly EmailNormalizer $emailNormalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function handle(array $payload): User
    {
        $email = (string) $payload['email'];
        $user = new User($email, $this->emailNormalizer->normalize($email));

        $user->setFirstName($payload['firstName'] ?? null);
        $user->setLastName($payload['lastName'] ?? null);
        $user->setIsActive($payload['isActive'] ?? true);
        $user->setRoles(($payload['isAdmin'] ?? false) ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER']);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, (string) $payload['password']));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
