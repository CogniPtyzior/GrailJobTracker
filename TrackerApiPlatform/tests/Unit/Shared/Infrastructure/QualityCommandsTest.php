<?php

declare(strict_types=1);

/*
 * Quality command documentation guard.
 * This keeps the Composer scripts used by the migration visible and executable from a single place.
 */

it('documents the quality commands in Composer scripts', function (): void {
    $composerPath = dirname(__DIR__, 4).'/composer.json';
    $composer = json_decode((string) file_get_contents($composerPath), true, 512, JSON_THROW_ON_ERROR);

    expect($composer['scripts'])
        ->toHaveKey('test', 'pest')
        ->toHaveKey('test:coverage', 'pest --coverage')
        ->toHaveKey('lint:container', '@php bin/console lint:container')
        ->toHaveKey('lint:yaml', '@php bin/console lint:yaml config');
});



