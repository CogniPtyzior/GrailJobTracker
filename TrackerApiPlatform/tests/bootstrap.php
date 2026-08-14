<?php

declare(strict_types=1);

/*
 * Test bootstrap for the API Platform backend.
 * It loads Composer and Symfony runtime settings before Pest or PHPUnit start the suite.
 */

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

if (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}
