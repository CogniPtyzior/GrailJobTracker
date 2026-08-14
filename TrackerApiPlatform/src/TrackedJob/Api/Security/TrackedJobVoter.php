<?php

declare(strict_types=1);

/*
 * Symfony voter for tracked job object authorization.
 * It keeps object-level ownership rules in the API security adapter without leaking Symfony into the domain model.
 */

namespace App\TrackedJob\Api\Security;

use App\Security\Infrastructure\Security\SecurityUser;
use App\TrackedJob\Domain\Entity\TrackedJob;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

final class TrackedJobVoter extends Voter
{
    public const string VIEW = 'TRACKED_JOB_VIEW';
    public const string UPDATE = 'TRACKED_JOB_UPDATE';
    public const string DELETE = 'TRACKED_JOB_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::UPDATE, self::DELETE], true)
            && $subject instanceof TrackedJob;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof SecurityUser || !$subject instanceof TrackedJob) {
            return false;
        }

        return $subject->ownerId()->equals($user->domainUser()->getId());
    }
}