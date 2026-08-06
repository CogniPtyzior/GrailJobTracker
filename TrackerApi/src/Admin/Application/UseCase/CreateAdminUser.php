<?php

namespace App\Admin\Application\UseCase;

use App\Admin\Application\Exception\AdminUserAlreadyExists;
use App\Admin\Application\Input\CreateAdminUserInput;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that creates an admin-managed user account.
 */
final class CreateAdminUser
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(CreateAdminUserInput $payload): User
    {
        $email = EmailAddress::fromString($payload->email);

        if ($this->userRepository->findOneByEmail($email) instanceof User) {
            throw new AdminUserAlreadyExists('A user with this email already exists.');
        }

        $user = new User($email);
        $user->updateProfile($payload->firstName, $payload->lastName);
        $payload->isActive ? $user->activate() : $user->deactivate();
        $user->updateAdminRole($payload->isAdmin);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload->password));

        $this->userRepository->save($user);
        $this->userRepository->flush();

        return $user;
    }
}
