<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

/**
 * Symfony kernel for the API Platform backend.
 *
 * The kernel only bootstraps framework configuration; business boundaries are organized inside feature modules.
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}