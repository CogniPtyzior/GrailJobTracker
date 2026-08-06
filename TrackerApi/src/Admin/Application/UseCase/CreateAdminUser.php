<?php

namespace App\Admin\Application\UseCase;

use App\Admin\Application\Exception\AdminUserAlreadyExists;
use App\Admin\Application\Input\CreateAdminUserInput;
use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that creates an admin-managed user account.
 */
final class CreateAdminUser
{
    public function __construct(
        private readonly EmailNormalizer $emailNormalizer,
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(CreateAdminUserInput $payload): User
    {
        $normalizedEmail = $this->emailNormalizer->normalize($payload->email);

        if ($this->userRepository->findOneByNormalizedEmail($normalizedEmail) instanceof User) {
            throw new AdminUserAlreadyExists('A user with this email already exists.');
        }

        $user = new User($payload->email, $normalizedEmail);
        $user->updateProfile($payload->firstName, $payload->lastName);
        $payload->isActive ? $user->activate() : $user->deactivate();
        $user->updateAdminRole($payload->isAdmin);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload->password));

        $this->userRepository->save($user);
        $this->userRepository->flush();

        return $user;
    }
}