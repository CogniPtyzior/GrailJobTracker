<?php

namespace App\Tests\Unit\Admin\Application;

use App\Admin\Application\UserPresenter;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class UserPresenterTest extends TestCase
{
    public function testPresentMapsUserToArray(): void
    {
        $user = UserBuilder::aUser()
            ->withRoles(['ROLE_ADMIN'])
            ->loggedInAt(FixedDates::april20())
            ->build();

        $presented = (new UserPresenter())->present($user);

        self::assertSame($user->getId()->toRfc4122(), $presented['id']);
        self::assertSame($user->getEmail(), $presented['email']);
        self::assertSame('John', $presented['firstName']);
        self::assertSame('Doe', $presented['lastName']);
        self::assertTrue($presented['isActive']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $presented['roles']);
        self::assertSame(FixedDates::april20()->format(\DateTimeInterface::ATOM), $presented['lastLoginAt']);
    }
}