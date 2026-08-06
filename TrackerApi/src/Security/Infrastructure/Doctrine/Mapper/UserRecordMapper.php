<?php

namespace App\Security\Infrastructure\Doctrine\Mapper;

use App\Security\Domain\Entity\User;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;

final class UserRecordMapper
{
    public function toDomain(UserRecord $record): User
    {
        return User::reconstitute(
            $record->getId(),
            $record->getEmail(),
            $record->getNormalizedEmail(),
            $record->getFirstName(),
            $record->getLastName(),
            $record->isActive(),
            $record->getRoles(),
            $record->getPasswordHash(),
            $record->getCreatedAt(),
            $record->getLastLoginAt(),
        );
    }

    public function updateRecord(User $user, UserRecord $record): void
    {
        $record->setId($user->getId());
        $record->setEmail($user->getEmail());
        $record->setNormalizedEmail($user->getNormalizedEmail());
        $record->setFirstName($user->getFirstName());
        $record->setLastName($user->getLastName());
        $record->setIsActive($user->isActive());
        $record->setRoles($user->getRoles());
        $record->setPasswordHash($user->getPassword());
        $record->setCreatedAt($user->getCreatedAt());
        $record->setLastLoginAt($user->getLastLoginAt());
    }
}