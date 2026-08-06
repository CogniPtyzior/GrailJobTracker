<?php

namespace App\Admin\Application\UseCase;

use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\Repository\AccessRequestRepositoryInterface;
use App\Admin\Application\Input\ApproveAccessRequestInput;
use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Application use case that approves an access request and provisions the related user.
 */
final class ApproveAccessRequest
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly AccessRequestRepositoryInterface $accessRequestRepository,
        private readonly EmailNormalizer $emailNormalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function handle(AccessRequest $accessRequest, ApproveAccessRequestInput $payload): User
    {
        $normalizedEmail = $this->emailNormalizer->normalize($accessRequest->getEmail());
        $user = $this->userRepository->findOneByNormalizedEmail($normalizedEmail);

        if (!$user instanceof User) {
            $user = new User($accessRequest->getEmail(), $normalizedEmail);
        }

        $user->updateProfile(
            $payload->firstName ?? $accessRequest->getFirstName(),
            $payload->lastName ?? $accessRequest->getLastName(),
        );
        $user->activate();
        $user->assignRegularUser();
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $payload->password));

        $this->userRepository->save($user);
        $this->accessRequestRepository->remove($accessRequest);
        $this->accessRequestRepository->flush();

        return $user;
    }
}