<?php

namespace App\Admin\Application;

use App\Security\Domain\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Application use case that deletes an admin-managed user.
 */
final class DeleteAdminUser
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function handle(User $user): void
    {
        $this->entityManager->remove($user);
        $this->entityManager->flush();
    }
}
