<?php

namespace App\Security\Infrastructure\Console;

use App\Security\Domain\Entity\User;
use App\Security\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\ValueObject\EmailAddress;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Security\Application\Security\PasswordHasherInterface;

#[AsCommand(name: 'app:bootstrap-admin', description: 'Create the bootstrap admin if it does not exist yet.')]
final class BootstrapAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepositoryInterface $userRepository,
        private readonly PasswordHasherInterface $passwordHasher,
        private readonly Connection $connection,
        private readonly string $adminBootstrapEmail,
        private readonly string $adminBootstrapPasswordFile,
        private readonly string $trackerSchema,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
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

        $this->connection->executeStatement(sprintf('CREATE SCHEMA IF NOT EXISTS %s', $this->trackerSchema));

        if ($this->connection->fetchOne(sprintf("SELECT to_regclass('%s.users')", $this->trackerSchema)) === null) {
            $output->writeln('<comment>Users table does not exist yet. Skipping admin seed.</comment>');
            return Command::SUCCESS;
        }

        $email = EmailAddress::fromString($this->adminBootstrapEmail);
        $existingUser = $this->userRepository->findOneByEmail($email);

        if ($existingUser instanceof User) {
            $existingUser->grantAdmin();

            if (!$existingUser->isActive()) {
                $existingUser->activate();
            }

            $this->userRepository->save($existingUser);
            $this->userRepository->flush();
            $output->writeln('<info>Bootstrap admin already exists.</info>');
            return Command::SUCCESS;
        }

        $user = new User($email);
        $user->grantAdmin();
        $user->setPasswordHash($this->passwordHasher->hash($user, $password));
        $this->userRepository->save($user);
        $this->userRepository->flush();

        $output->writeln('<info>Bootstrap admin created.</info>');
        return Command::SUCCESS;
    }
}
