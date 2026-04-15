<?php

namespace App\Security\Infrastructure\Console;

use App\Security\Application\EmailNormalizer;
use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Doctrine\UserRepository;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:bootstrap-admin', description: 'Create the bootstrap admin if it does not exist yet.')]
final class BootstrapAdminCommand extends Command
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly EmailNormalizer $emailNormalizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager,
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

        $normalizedEmail = $this->emailNormalizer->normalize($this->adminBootstrapEmail);
        $existingUser = $this->userRepository->findOneByNormalizedEmail($normalizedEmail);

        if ($existingUser instanceof User) {
            $existingUser->setRoles(['ROLE_ADMIN', 'ROLE_USER']);

            if (!$existingUser->isActive()) {
                $existingUser->setIsActive(true);
            }

            $this->entityManager->flush();
            $output->writeln('<info>Bootstrap admin already exists.</info>');

            return Command::SUCCESS;
        }

        $user = new User($this->adminBootstrapEmail, $normalizedEmail);
        $user->setRoles(['ROLE_ADMIN', 'ROLE_USER']);
        $user->setPasswordHash($this->passwordHasher->hashPassword($user, $password));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $output->writeln('<info>Bootstrap admin created.</info>');

        return Command::SUCCESS;
    }
}
