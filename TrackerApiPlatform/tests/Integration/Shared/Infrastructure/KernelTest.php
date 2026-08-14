<?php

declare(strict_types=1);

/*
 * Smoke tests for the Symfony kernel foundation.
 * They prove that the API Platform backend can boot under Pest before business migration starts.
 */

use App\Kernel;

it('boots the Symfony test kernel', function (): void {
    self::bootKernel();

    expect(self::$kernel)
        ->toBeInstanceOf(Kernel::class)
        ->and(self::$kernel->getEnvironment())
        ->toBe('test');
});
