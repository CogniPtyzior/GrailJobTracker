<?php

declare(strict_types=1);

/*
 * Unit tests for tracked job object authorization.
 * They verify that the Symfony voter grants access only to the aggregate owner.
 */

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Security\SecurityUser;
use App\TrackedJob\Api\Security\TrackedJobVoter;
use App\TrackedJob\Domain\Entity\TrackedJob;
use App\Shared\Domain\ValueObject\EmailAddress;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

it('grants tracked job actions to the owner', function (string $attribute): void {
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $trackedJob = TrackedJob::openFor($owner->getId());
    $token = new UsernamePasswordToken(new SecurityUser($owner), 'main', ['ROLE_USER']);

    $vote = (new TrackedJobVoter())->vote($token, $trackedJob, [$attribute]);

    expect($vote)->toBe(VoterInterface::ACCESS_GRANTED);
})->with([
    TrackedJobVoter::VIEW,
    TrackedJobVoter::UPDATE,
    TrackedJobVoter::DELETE,
]);

it('denies tracked job actions to another authenticated user', function (): void {
    $owner = new User(EmailAddress::fromString('owner@example.com'));
    $other = new User(EmailAddress::fromString('other@example.com'));
    $trackedJob = TrackedJob::openFor($owner->getId());
    $token = new UsernamePasswordToken(new SecurityUser($other), 'main', ['ROLE_USER']);

    $vote = (new TrackedJobVoter())->vote($token, $trackedJob, [TrackedJobVoter::VIEW]);

    expect($vote)->toBe(VoterInterface::ACCESS_DENIED);
});

it('abstains from unsupported subjects and attributes', function (): void {
    $user = new User(EmailAddress::fromString('owner@example.com'));
    $token = new UsernamePasswordToken(new SecurityUser($user), 'main', ['ROLE_USER']);

    expect((new TrackedJobVoter())->vote($token, new stdClass(), [TrackedJobVoter::VIEW]))
        ->toBe(VoterInterface::ACCESS_ABSTAIN)
        ->and((new TrackedJobVoter())->vote($token, TrackedJob::openFor($user->getId()), ['UNKNOWN']))
        ->toBe(VoterInterface::ACCESS_ABSTAIN);
});