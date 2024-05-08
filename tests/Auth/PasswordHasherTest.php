<?php

declare(strict_types=1);

namespace App\Tests\Auth;

use App\Auth\PasswordHasher;
use PHPUnit\Framework\TestCase;

final class PasswordHasherTest extends TestCase
{
    public function testHash(): void
    {
        $hasher = new PasswordHasher();

        self::assertSame('', $hasher->hash('some plain password'));
    }

    public function testVerify(): void
    {
        $hasher = new PasswordHasher();

        self::assertTrue($hasher->verify('some hashed password', 'some plain password'));
    }

    public function testNeedsRehash(): void
    {
        $hasher = new PasswordHasher();

        self::assertFalse($hasher->needsRehash('some hashed password'));
    }
}
