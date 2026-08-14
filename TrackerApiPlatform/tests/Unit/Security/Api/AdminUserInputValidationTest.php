<?php

declare(strict_types=1);

/*
 * Unit tests for admin user API input validation.
 * They lock the Symfony Validator constraints used by API Platform before processors call application use cases.
 */

use App\Security\Api\Input\CreateAdminUserInput;
use App\Security\Api\Input\UpdateAdminUserInput;
use App\Security\Api\Serializer\AdminUserUpdateInputDenormalizer;
use Symfony\Component\Validator\Validation;

it('accepts valid admin user creation payloads', function (): void {
    $input = new CreateAdminUserInput();
    $input->email = 'candidate@example.com';
    $input->password = 'Password1!';
    $input->firstName = 'Ada';
    $input->lastName = 'Lovelace';

    expect(adminUserInputViolationPaths($input))->toBe([]);
});

it('preserves create password and profile validation constraints', function (): void {
    $input = new CreateAdminUserInput();
    $input->email = 'not-an-email';
    $input->password = 'password';
    $input->firstName = str_repeat('a', 121);
    $input->lastName = str_repeat('a', 121);

    $paths = adminUserInputViolationPaths($input);

    expect($paths)->toContain('email')
        ->and($paths)->toContain('password')
        ->and($paths)->toContain('firstName')
        ->and($paths)->toContain('lastName');
});

it('preserves update password and required boolean flag validation constraints', function (): void {
    $input = new UpdateAdminUserInput();
    $input->password = 'Password';
    $input->isActive = null;
    $input->isAdmin = null;
    $input->setProvidedFields(['password', 'isActive', 'isAdmin']);

    $paths = adminUserInputViolationPaths($input);

    expect($paths)->toContain('password')
        ->and($paths)->toContain('isActive')
        ->and($paths)->toContain('isAdmin');
});

it('records update fields without coercing string booleans', function (): void {
    $input = (new AdminUserUpdateInputDenormalizer())->denormalize(
        ['firstName' => 'Ada', 'isActive' => 'false', 'isAdmin' => true],
        UpdateAdminUserInput::class,
    );

    expect($input->has('firstName'))->toBeTrue()
        ->and($input->has('isActive'))->toBeTrue()
        ->and($input->has('isAdmin'))->toBeTrue()
        ->and($input->firstName)->toBe('Ada')
        ->and($input->isActive)->toBeNull()
        ->and($input->isAdmin)->toBeTrue()
        ->and(adminUserInputViolationPaths($input))->toContain('isActive');
});

/**
 * @return list<string>
 */
function adminUserInputViolationPaths(object $input): array
{
    return array_values(array_unique(array_map(
        static fn ($violation): string => $violation->getPropertyPath(),
        iterator_to_array(Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($input)),
    )));
}
