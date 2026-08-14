<?php

declare(strict_types=1);

/*
 * Mapper from admin API input DTOs to application inputs.
 * It converts simple transport fields into value objects before use cases run.
 */

namespace App\Security\Api\Mapper;

use App\Security\Api\Input\CreateAdminUserInput as ApiCreateAdminUserInput;
use App\Security\Api\Input\UpdateAdminUserInput as ApiUpdateAdminUserInput;
use App\Security\Application\Input\CreateAdminUserInput;
use App\Security\Application\Input\UpdateAdminUserInput;
use App\Shared\Domain\ValueObject\PersonName;

final readonly class AdminUserInputMapper
{
    public function toCreateInput(ApiCreateAdminUserInput $input): CreateAdminUserInput
    {
        return new CreateAdminUserInput(
            email: $input->email,
            password: $input->password,
            firstName: PersonName::fromNullable($input->firstName),
            lastName: PersonName::fromNullable($input->lastName),
            isActive: $input->isActive,
            isAdmin: $input->isAdmin,
        );
    }

    public function toUpdateInput(ApiUpdateAdminUserInput $input): UpdateAdminUserInput
    {
        return new UpdateAdminUserInput(
            firstName: PersonName::fromNullable($input->firstName),
            lastName: PersonName::fromNullable($input->lastName),
            isActive: $input->isActive,
            isAdmin: $input->isAdmin,
            password: $input->password,
            providedFields: $input->providedFields(),
        );
    }
}
