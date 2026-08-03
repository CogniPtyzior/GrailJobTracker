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

    public function handle(CreateAdminUserInput $payload): User
    {
        $user = new User($payload->email, $this->emailNormalizer->normalize($payload->email));

        $user->setFirstName($payload->firstName);
        $user->setLastName($payload->lastName);
        $user->setIsActive($payload->isActive);
        $user->setRoles($payload->isAdmin ? ['ROLE_ADMIN', 'ROLE_USER'] : ['ROLE_USER']);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload->password));

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
