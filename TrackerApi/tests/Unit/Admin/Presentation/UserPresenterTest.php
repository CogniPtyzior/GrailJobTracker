<?php

namespace App\Tests\Unit\Admin\Presentation;

use App\Admin\Presentation\UserPresenter;
use App\Admin\Presentation\View\UserView;
use App\Tests\Support\Builder\UserBuilder;
use App\Tests\Support\Date\FixedDates;
use PHPUnit\Framework\TestCase;

final class UserPresenterTest extends TestCase
{
    public function testPresentMapsUserToTypedView(): void
    {
        $user = UserBuilder::aUser()
            ->withRoles(['ROLE_ADMIN'])
            ->loggedInAt(FixedDates::april20())
            ->build();

        $view = (new UserPresenter())->present($user);

        self::assertInstanceOf(UserView::class, $view);

        $presented = $view->toArray();

        self::assertSame($user->getId()->toRfc4122(), $presented['id']);
        self::assertSame($user->getEmail(), $presented['email']);
        self::assertSame('John', $presented['firstName']);
        self::assertSame('Doe', $presented['lastName']);
        self::assertTrue($presented['isActive']);
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $presented['roles']);
        self::assertSame(FixedDates::april20()->format(\DateTimeInterface::ATOM), $presented['lastLoginAt']);
    }
}
