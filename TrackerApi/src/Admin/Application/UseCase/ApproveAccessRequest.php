<?php

namespace App\Admin\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\Admin\Application\Input\ApproveAccessRequestInput;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Security\Application\Security\PasswordHasherInterface;

/**
 * Application use case that approves an access request and provisions the related user.
 */
final class ApproveAccessRequest
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
        private readonly PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(AccessRequest $accessRequest, ApproveAccessRequestInput $payload): User
    {
        $email = EmailAddress::fromString($accessRequest->getEmail());
        $user = $this->userRepository->findOneByEmail($email);

        if (!$user instanceof User) {
            $user = new User($email);
        }

        $user->updateProfile(
            $payload->firstName ?? $accessRequest->firstName(),
            $payload->lastName ?? $accessRequest->lastName(),
        );
        $user->activate();
        $user->assignRegularUser();
        $user->setPasswordHash($this->passwordHasher->hash($user, $payload->password));

        $this->userRepository->save($user);
        $this->accessRequestRepository->remove($accessRequest);
        $this->accessRequestRepository->flush();

        return $user;
    }
}

