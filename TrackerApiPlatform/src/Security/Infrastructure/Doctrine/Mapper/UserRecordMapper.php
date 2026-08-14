<?php

declare(strict_types=1);

/*
 * Mapper between the user aggregate and the Doctrine user record.
 * It is the explicit boundary between the domain model and the preserved database schema.
 */

namespace App\Security\Infrastructure\Doctrine\Mapper;

use App\Security\Domain\Entity\User;
use App\Security\Domain\ValueObject\UserId;
use App\Security\Domain\ValueObject\UserRoles;
use App\Security\Infrastructure\Doctrine\Entity\UserRecord;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Shared\Domain\ValueObject\PersonName;
use Symfony\Component\Uid\Uuid;

final class UserRecordMapper
{
    public function toDomain(UserRecord $record): User
    {
        return User::reconstitute(
            UserId::fromString($record->getId()->toRfc4122()),
            EmailAddress::reconstitute($record->getEmail(), $record->getNormalizedEmail()),
            PersonName::fromNullable($record->getFirstName()),
            PersonName::fromNullable($record->getLastName()),
            $record->isActive(),
            UserRoles::fromArray($record->getRoles()),
            $record->getPasswordHash(),
            $record->getCreatedAt(),
            $record->getLastLoginAt(),
        );
    }

    public function updateRecord(User $user, UserRecord $record): void
    {
        $record->setId(Uuid::fromString($user->getId()->toRfc4122()));
        $record->setEmail($user->getEmail());
        $record->setNormalizedEmail($user->getNormalizedEmail());
        $record->setFirstName($user->firstName()?->value());
        $record->setLastName($user->lastName()?->value());
        $record->setIsActive($user->isActive());
        $record->setRoles($user->roles()->toArray());
        $record->setPasswordHash($user->getPassword());
        $record->setCreatedAt($user->getCreatedAt());
        $record->setLastLoginAt($user->getLastLoginAt());
    }
}
