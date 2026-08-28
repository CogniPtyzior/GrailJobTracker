<?php

declare(strict_types=1);

/*
 * Unit coverage for the bootstrap admin console adapter.
 * It verifies the legacy seed behavior without booting Symfony or touching the shared PostgreSQL database.
 */

use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Console\BootstrapAdminCommand;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Fake\InMemoryTransactionManager;
use App\Tests\Support\Fake\InMemoryUserRepository;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

it('skips bootstrap admin creation when the password file is missing', function (): void {
    $repository = new InMemoryUserRepository();
    $transactionManager = new InMemoryTransactionManager();
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->never())->method('executeStatement');

    $tester = new CommandTester(new BootstrapAdminCommand(
        $repository,
        staticPasswordHasher(),
        $connection,
        $transactionManager,
        'admin@example.local',
        __DIR__.'/missing.secret',
        'trackers',
    ));

    expect($tester->execute([]))->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Bootstrap password file is missing or unreadable.')
        ->and($repository->saveCalls)->toBe(0)
        ->and($transactionManager->transactionCalls)->toBe(0);
});

it('rejects unsafe tracker schema names before running SQL', function (): void {
    $connection = $this->createMock(Connection::class);
    $connection->expects($this->never())->method('executeStatement');

    $tester = new CommandTester(new BootstrapAdminCommand(
        new InMemoryUserRepository(),
        staticPasswordHasher(),
        $connection,
        new InMemoryTransactionManager(),
        'admin@example.local',
        createBootstrapPasswordFile('secret-password'),
        'trackers;drop',
    ));

    expect($tester->execute([]))->toBe(Command::FAILURE)
        ->and($tester->getDisplay())->toContain('Tracker schema name is invalid.');
});

it('creates the bootstrap admin when the users table exists and no admin exists yet', function (): void {
    $repository = new InMemoryUserRepository();
    $transactionManager = new InMemoryTransactionManager();
    $connection = connectionForExistingUsersTable();
    $passwordFile = createBootstrapPasswordFile('secret-password');

    $tester = new CommandTester(new BootstrapAdminCommand(
        $repository,
        staticPasswordHasher(),
        $connection,
        $transactionManager,
        'admin@example.local',
        $passwordFile,
        'trackers',
    ));

    expect($tester->execute([]))->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Bootstrap admin created.')
        ->and($transactionManager->transactionCalls)->toBe(1);

    $admin = $repository->findOneByEmail(EmailAddress::fromString('admin@example.local'));

    expect($admin)->toBeInstanceOf(User::class)
        ->and($admin->isActive())->toBeTrue()
        ->and($admin->getRoles())->toContain('ROLE_ADMIN')
        ->and($admin->getPassword())->toBe('hashed-secret-password');
});

it('reactivates and promotes an existing bootstrap admin', function (): void {
    $repository = new InMemoryUserRepository();
    $transactionManager = new InMemoryTransactionManager();
    $admin = new User(EmailAddress::fromString('admin@example.local'));
    $admin->deactivate();
    $admin->assignRegularUser();
    $repository->add($admin);

    $tester = new CommandTester(new BootstrapAdminCommand(
        $repository,
        staticPasswordHasher(),
        connectionForExistingUsersTable(),
        $transactionManager,
        'admin@example.local',
        createBootstrapPasswordFile('secret-password'),
        'trackers',
    ));

    expect($tester->execute([]))->toBe(Command::SUCCESS)
        ->and($tester->getDisplay())->toContain('Bootstrap admin already exists.')
        ->and($admin->isActive())->toBeTrue()
        ->and($admin->getRoles())->toContain('ROLE_ADMIN')
        ->and($transactionManager->transactionCalls)->toBe(1);
});

function createBootstrapPasswordFile(string $password): string
{
    $path = tempnam(sys_get_temp_dir(), 'bootstrap-admin-');

    if ($path === false) {
        throw new RuntimeException('Unable to create a temporary password file.');
    }

    file_put_contents($path, $password);

    return $path;
}

function connectionForExistingUsersTable(): Connection
{
    $connection = test()->createMock(Connection::class);
    $connection->expects(test()->once())
        ->method('executeStatement')
        ->with('CREATE SCHEMA IF NOT EXISTS trackers');
    $connection->expects(test()->once())
        ->method('fetchOne')
        ->with('SELECT to_regclass(?)', ['trackers.users'])
        ->willReturn('trackers.users');

    return $connection;
}

function staticPasswordHasher(): PasswordHasherInterface
{
    return new class implements PasswordHasherInterface {
        public function hash(User $user, string $plainPassword): string
        {
            return 'hashed-'.$plainPassword;
        }
    };
}
