<?php

declare(strict_types=1);

/*
 * Pest entrypoint for the API Platform backend test suite.
 * Shared expectations, architecture checks and Symfony-specific helpers should be registered here.
 */

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

pest()->extend(KernelTestCase::class)->in('Integration');
