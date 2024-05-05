<?php

declare(strict_types=1);

namespace App\Auth;

use SensitiveParameter;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class PasswordHasher implements PasswordHasherInterface
{
    public function hash(#[SensitiveParameter] string $plainPassword): string
    {
        return '';
    }

    public function verify(string $hashedPassword, #[SensitiveParameter] string $plainPassword): bool
    {
        return true;
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
