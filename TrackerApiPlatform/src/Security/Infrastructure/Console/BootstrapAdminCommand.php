<?php

declare(strict_types=1);

/*
 * Console adapter provisioning the bootstrap administrator used by local and production-like runtimes.
 * It preserves the legacy seed behavior while depending only on application/domain ports for user creation.
 */

namespace App\Security\Infrastructure\Console;

use App\Security\Application\Security\PasswordHasherInterface;
use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Application\Transaction\TransactionManagerInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:bootstrap-admin', description: 'Create the bootstrap admin if it does not exist yet.')]
final class BootstrapAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly Connection $connection,
        private readonly TransactionManagerInterface $transactionManager,
        private readonly string $adminBootstrapEmail,
        private readonly string $adminBootstrapPasswordFile,
        private readonly string $trackerSchema,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $schema = trim($this->trackerSchema);

        if (!$this->isValidSchemaName($schema)) {
            $output->writeln('<error>Tracker schema name is invalid. Skipping admin seed.</error>');

            return Command::FAILURE;
        }

        $passwordFile = trim($this->adminBootstrapPasswordFile);

        if ($passwordFile === '' || !is_readable($passwordFile)) {
            $output->writeln('<comment>Bootstrap password file is missing or unreadable. Skipping admin seed.</comment>');

            return Command::SUCCESS;
        }

        $password = trim((string) file_get_contents($passwordFile));

        if ($password === '') {
            $output->writeln('<comment>Bootstrap password file is empty. Skipping admin seed.</comment>');

            return Command::SUCCESS;
        }

        $this->connection->executeStatement(sprintf('CREATE SCHEMA IF NOT EXISTS %s', $schema));

        if ($this->connection->fetchOne('SELECT to_regclass(?)', [sprintf('%s.users', $schema)]) === null) {
            $output->writeln('<comment>Users table does not exist yet. Skipping admin seed.</comment>');

            return Command::SUCCESS;
        }

        $email = EmailAddress::fromString($this->adminBootstrapEmail);
        $existingUser = $this->userRepository->findOneByEmail($email);

        if ($existingUser instanceof User) {
            $this->transactionManager->transactional(function () use ($existingUser): void {
                $existingUser->grantAdmin();

                if (!$existingUser->isActive()) {
                    $existingUser->activate();
                }

                $this->userRepository->save($existingUser);
            });

            $output->writeln('<info>Bootstrap admin already exists.</info>');

            return Command::SUCCESS;
        }

        $this->transactionManager->transactional(function () use ($email, $password): void {
            $user = new User($email);
            $user->grantAdmin();
            $user->setPasswordHash($this->passwordHasher->hash($user, $password));
            $this->userRepository->save($user);
        });

        $output->writeln('<info>Bootstrap admin created.</info>');

        return Command::SUCCESS;
    }

    private function isValidSchemaName(string $schema): bool
    {
        return preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $schema) === 1;
    }
}
