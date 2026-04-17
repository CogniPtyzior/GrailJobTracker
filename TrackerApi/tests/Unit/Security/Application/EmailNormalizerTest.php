<?php

namespace App\Tests\Unit\Security\Application;

use App\Security\Application\EmailNormalizer;
use PHPUnit\Framework\TestCase;

final class EmailNormalizerTest extends TestCase
{
    public function testNormalizeTrimsAndLowercasesEmail(): void
    {
        $normalizer = new EmailNormalizer();

        self::assertSame('john.doe+test@example.com', $normalizer->normalize('  John.Doe+Test@Example.com  '));
    }
}