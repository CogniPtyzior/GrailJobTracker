<?php

declare(strict_types=1);

/*
 * Unit tests for admin user API adapters.
 * They verify DTO mapping, provider query normalization and processor orchestration outside full HTTP.
 */

use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Security\Api\Input\CreateAdminUserInput as ApiCreateAdminUserInput;
use App\Security\Api\Mapper\AdminUserApiMapper;
use App\Security\Api\Mapper\AdminUserInputMapper;
use App\Security\Api\Processor\CreateAdminUserProcessor;
use App\Security\Api\Processor\DeleteAdminUserProcessor;
use App\Security\Api\Provider\AdminUserCollectionProvider;
use App\Security\Api\Security\AuthenticatedUserResolver;
use App\Security\Application\UseCase\CreateAdminUser;
use App\Security\Application\UseCase\DeleteAdminUser;
use App\Security\Application\UseCase\GetAdminUser;
use App\Security\Application\UseCase\SearchUsers;
use App\Security\Domain\Entity\User;
use App\Shared\Domain\ValueObject\EmailAddress;
use App\Tests\Support\Fake\InMemoryUserRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

it('maps admin user collections with pagination metadata', function (): void {
    $user = adminUserApiFixture('list@example.com');
    $users = adminUserApiRepositoryWith($user);
    $provider = new AdminUserCollectionProvider(new SearchUsers($users), new AdminUserApiMapper());

    $output = $provider->provide(new Get(), context: ['filters' => ['page' => '2', 'pageSize' => '5', 'isActive' => 'true']]);

    expect($output->items[0]->email)->toBe('list@example.com')
        ->and($output->page)->toBe(2)
        ->and($output->pageSize)->toBe(5)
        ->and($output->total)->toBe(1);
});

it('creates admin users through the creation processor', function (): void {
    $users = new InMemoryUserRepository();
    $processor = new CreateAdminUserProcessor(
        new CreateAdminUser($users, adminUserApiPasswordHasher()),
        new AdminUserInputMapper(),
        new AdminUserApiMapper(),
    );
    $input = new ApiCreateAdminUserInput();
    $input->email = 'processor@example.com';
    $input->password = 'Password1!';
    $input->firstName = 'Ada';
    $input->isAdmin = true;

    $output = $processor->process($input, new Post());

    expect($output->item->email)->toBe('processor@example.com')
        ->and($output->item->firstName)->toBe('Ada')
        ->and($output->item->roles)->toBe(['ROLE_ADMIN', 'ROLE_USER']);
});

it('deletes admin users through the deletion processor', function (): void {
    $admin = adminUserApiFixture('admin@example.com', true);
    $managed = adminUserApiFixture('managed@example.com');
    $users = adminUserApiRepositoryWith($admin, $managed);
    $processor = new DeleteAdminUserProcessor(
        adminUserApiResolver($admin),
        new GetAdminUser($users),
        new DeleteAdminUser($users, 'admin@example.com'),
    );

    $processor->process(null, new Delete(), ['id' => $managed->getId()->toRfc4122()]);

    expect($users->getById($managed->getId()))->toBeNull();
});

function adminUserApiFixture(string $email, bool $admin = false): User
{
    $user = new User(EmailAddress::fromString($email));
    $user->updateAdminRole($admin);

    return $user;
}

function adminUserApiResolver(User $user): AuthenticatedUserResolver
{
    $tokenStorage = new class($user) implements TokenStorageInterface {
        public function __construct(private User $user)
        {
        }

        public function getToken(): ?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface
        {
            return new UsernamePasswordToken(new \App\Security\Infrastructure\Security\SecurityUser($this->user), 'main');
        }

        public function setToken(?\Symfony\Component\Security\Core\Authentication\Token\TokenInterface $token = null): void
        {
        }
    };

    return new AuthenticatedUserResolver($tokenStorage);
}

function adminUserApiRepositoryWith(User ...$users): InMemoryUserRepository
{
    $repository = new InMemoryUserRepository();

    foreach ($users as $user) {
        $repository->add($user);
    }

    return $repository;
}

function adminUserApiPasswordHasher(): \App\Security\Application\Security\PasswordHasherInterface
{
    return new class implements \App\Security\Application\Security\PasswordHasherInterface {
        public function hash(User $user, string $plainPassword): string
        {
            return 'hashed::'.$plainPassword;
        }
    };
}
