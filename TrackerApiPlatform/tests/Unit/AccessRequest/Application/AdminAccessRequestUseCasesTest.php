<?php

declare(strict_types=1);

/*
 * Unit tests for admin access request application use cases.
 * They cover user provisioning and request deletion without involving API Platform or Doctrine.
 */

use App\AccessRequest\Application\Input\ApproveAccessRequestInput;
use App\AccessRequest\Application\UseCase\ApproveAccessRequest;
use App\AccessRequest\Application\UseCase\DeleteAccessRequest;
use App\AccessRequest\Domain\Entity\AccessRequest;
use App\AccessRequest\Domain\ValueObject\AccessRequestCompanyName;
use App\AccessRequest\Domain\ValueObject\AccessRequestReason;
use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use App\Tests\Support\Fake\InMemoryAccessRequestRepository;
use App\Tests\Support\Fake\InMemoryUserRepository;
use App\Tests\Support\Fake\InMemoryTransactionManager;

it('approves an access request by creating an active regular user and removing the request', function (): void {
    $accessRequest = adminUseCaseAccessRequest('Candidate@example.com', 'RequestFirst', 'RequestLast');
    $accessRequests = new InMemoryAccessRequestRepository([$accessRequest]);
    $users = new InMemoryUserRepository();
    $transactionManager = new InMemoryTransactionManager();
    $useCase = new ApproveAccessRequest($users, $accessRequests, adminUseCasePasswordHasher(), $transactionManager);

    $user = $useCase->handle($accessRequest, new ApproveAccessRequestInput('Password1!', null, null));

    expect($user->getEmail())->toBe('Candidate@example.com')
        ->and($user->getNormalizedEmail())->toBe('candidate@example.com')
        ->and($user->firstName()?->value())->toBe('RequestFirst')
        ->and($user->lastName()?->value())->toBe('RequestLast')
        ->and($user->isActive())->toBeTrue()
        ->and($user->getRoles())->toBe(['ROLE_USER'])
        ->and($user->getPassword())->toBe('hashed::Password1!')
        ->and($accessRequests->getById($accessRequest->getId()))->toBeNull()
        ->and($users->findOneByEmail(EmailAddress::fromString('candidate@example.com')))->toBe($user);
});

it('approves an access request by reusing existing users and preferring payload names', function (): void {
    $accessRequest = adminUseCaseAccessRequest('Candidate@example.com', 'RequestFirst', 'RequestLast');
    $accessRequests = new InMemoryAccessRequestRepository([$accessRequest]);
    $users = new InMemoryUserRepository();
    $transactionManager = new InMemoryTransactionManager();
    $existingUser = new User(EmailAddress::fromString('candidate@example.com'));
    $existingUser->deactivate();
    $existingUser->grantAdmin();
    $users->add($existingUser);
    $useCase = new ApproveAccessRequest($users, $accessRequests, adminUseCasePasswordHasher(), $transactionManager);

    $user = $useCase->handle(
        $accessRequest,
        new ApproveAccessRequestInput(
            'Password1!',
            PersonName::fromNullable('Approved'),
            PersonName::fromNullable('Person'),
        ),
    );

    expect($user)->toBe($existingUser)
        ->and($user->firstName()?->value())->toBe('Approved')
        ->and($user->lastName()?->value())->toBe('Person')
        ->and($user->isActive())->toBeTrue()
        ->and($user->getRoles())->toBe(['ROLE_USER'])
        ->and($user->getPassword())->toBe('hashed::Password1!');
});

it('deletes an access request through the repository port', function (): void {
    $accessRequest = adminUseCaseAccessRequest('delete@example.com');
    $repository = new InMemoryAccessRequestRepository([$accessRequest]);
    $transactionManager = new InMemoryTransactionManager();

    (new DeleteAccessRequest($repository, $transactionManager))->handle($accessRequest);

    expect($repository->getById($accessRequest->getId()))->toBeNull()
        ->and($repository->removeCalls)->toBe(1)
        ->and($transactionManager->transactionCalls)->toBe(1);
});

function adminUseCaseAccessRequest(
    string $email,
    ?string $firstName = null,
    ?string $lastName = null,
): AccessRequest {
    return AccessRequest::submit(
        EmailAddress::fromString($email),
        AccessRequestCompanyName::fromString('Acme'),
        AccessRequestReason::fromString('This request should be approved by an administrator.'),
        PersonName::fromNullable($firstName),
        PersonName::fromNullable($lastName),
    );
}

function adminUseCasePasswordHasher(): PasswordHasherInterface
{
    return new class implements PasswordHasherInterface {
        public function hash(User $user, string $plainPassword): string
        {
            return 'hashed::'.$plainPassword;
        }
    };
}
