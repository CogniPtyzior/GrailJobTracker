<?php

declare(strict_types=1);

/*
 * Application use case approving a public access request.
 * It provisions or reactivates the matching user, assigns regular-user roles and removes the consumed request.
 */

namespace App\AccessRequest\Application\UseCase;

use App\AccessRequest\Application\Input\ApproveAccessRequestInput;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;

final readonly class ApproveAccessRequest
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private AccessRequestRepositoryInterface $accessRequestRepository,
        private PasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(AccessRequest $accessRequest, ApproveAccessRequestInput $input): User
    {
        $email = EmailAddress::fromString($accessRequest->getEmail());
        $user = $this->userRepository->findOneByEmail($email) ?? new User($email);

        $user->updateProfile(
            $input->firstName ?? $accessRequest->firstName(),
            $input->lastName ?? $accessRequest->lastName(),
        );
        $user->activate();
        $user->assignRegularUser();
        $user->setPasswordHash($this->passwordHasher->hash($user, $input->password));

        $this->userRepository->save($user);
        $this->accessRequestRepository->remove($accessRequest);
        $this->accessRequestRepository->flush();

        return $user;
    }
}
