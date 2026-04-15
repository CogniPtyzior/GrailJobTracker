<?php

namespace App\Admin\Application;

use App\Security\Domain\Entity\User;

final class UserPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function present(User $user): array
    {
        return [
            'id' => $user->getId()->toRfc4122(),
            'email' => $user->getEmail(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'isActive' => $user->isActive(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lastLoginAt' => $user->getLastLoginAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
