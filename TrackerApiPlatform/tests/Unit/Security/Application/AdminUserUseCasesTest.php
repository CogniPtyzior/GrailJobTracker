<?php

declare(strict_types=1);

/*
 * Unit tests for admin user application use cases.
 * They verify orchestration and bootstrap admin rules without Symfony HTTP, Doctrine or API Platform.
 */

use App\Security\Application\Input\CreateAdminUserInput;
use App\Security\Application\Input\UpdateAdminUserInput;
use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Application\UseCase\CreateAdminUser;
use App\Security\Application\UseCase\DeleteAdminUser;
use App\Security\Application\UseCase\GetAdminUser;
use App\Security\Application\UseCase\UpdateAdminUser;
use App\Security\Domain\Entity\User;
use App\Shared\Application\Exception\ApplicationConflict;
use App\Shared\Application\Exception\InvalidApplicationCommand;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use App\Tests\Support\Fake\InMemoryUserRepository;

it('creates an admin-managed user through the repository port', function (): void {
    $users = new InMemoryUserRepository();

    $user = (new CreateAdminUser($users, adminUserUseCasePasswordHasher()))->handle(new CreateAdminUserInput(
        email: 'New.User@example.com',
        password: 'Password1!',
        firstName: PersonName::fromNullable('New'),
        lastName: PersonName::fromNullable('User'),
        isActive: true,
        isAdmin: false,
    ));

    expect($user->getEmail())->toBe('New.User@example.com')
        ->and($user->getNormalizedEmail())->toBe('new.user@example.com')
        ->and($user->firstName()?->value())->toBe('New')
        ->and($user->lastName()?->value())->toBe('User')
        ->and($user->getRoles())->toBe(['ROLE_USER'])
        ->and($user->getPassword())->toBe('hashed::Password1!')
        ->and($users->getById($user->getId()))->toBe($user)
        ->and($users->saveCalls)->toBe(1)
        ->and($users->flushCalls)->toBe(1);
});

it('rejects duplicate admin user emails', function (): void {
    $users = adminUserUseCaseRepositoryWith(adminUserUseCaseFixture('existing@example.com'));
    $useCase = new CreateAdminUser($users, adminUserUseCasePasswordHasher());

    expect(fn () => $useCase->handle(new CreateAdminUserInput(
        email: 'EXISTING@example.com',
        password: 'Password1!',
        firstName: null,
        lastName: null,
        isActive: true,
        isAdmin: false,
    )))->toThrow(ApplicationConflict::class, 'A user with this email already exists.');
});

it('keeps bootstrap admin active and admin when a partial update tries to demote it', function (): void {
    $bootstrap = adminUserUseCaseFixture('bootstrap@example.com', true);
    $users = adminUserUseCaseRepositoryWith($bootstrap);

    $updated = (new UpdateAdminUser(adminUserUseCasePasswordHasher(), $users, 'bootstrap@example.com'))->handle(
        $bootstrap,
        $bootstrap,
        new UpdateAdminUserInput(
            firstName: PersonName::fromNullable('Updated'),
            lastName: null,
            isActive: false,
            isAdmin: false,
            password: null,
            providedFields: ['firstName', 'isActive', 'isAdmin'],
        ),
    );

    expect($updated->isActive())->toBeTrue()
        ->and($updated->getRoles())->toBe(['ROLE_ADMIN', 'ROLE_USER'])
        ->and($updated->firstName()?->value())->toBe('Updated');
});

it('deletes non-bootstrap users and rejects bootstrap deletion', function (): void {
    $admin = adminUserUseCaseFixture('admin@example.com', true);
    $managed = adminUserUseCaseFixture('managed@example.com');
    $users = adminUserUseCaseRepositoryWith($admin, $managed);
    $delete = new DeleteAdminUser($users, 'admin@example.com');

    $delete->handle($managed, $admin);

    expect($users->getById($managed->getId()))->toBeNull()
        ->and(fn () => $delete->handle($admin, $admin))
        ->toThrow(InvalidApplicationCommand::class, 'The bootstrap admin cannot be deleted.');
});

it('loads admin users by id through the repository port', function (): void {
    $user = adminUserUseCaseFixture('load@example.com');
    $users = adminUserUseCaseRepositoryWith($user);

    expect((new GetAdminUser($users))->handle($user->getId()))->toBe($user);
});

function adminUserUseCaseFixture(string $email, bool $admin = false): User
{
    $user = new User(EmailAddress::fromString($email));
    $user->updateAdminRole($admin);

    return $user;
}

function adminUserUseCasePasswordHasher(): PasswordHasherInterface
{
    return new class implements PasswordHasherInterface {
        public function hash(User $user, string $plainPassword): string
        {
            return 'hashed::'.$plainPassword;
        }
    };
}

function adminUserUseCaseRepositoryWith(User ...$users): InMemoryUserRepository
{
    $repository = new InMemoryUserRepository();

    foreach ($users as $user) {
        $repository->add($user);
    }

    return $repository;
}
