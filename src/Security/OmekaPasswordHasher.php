<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\PasswordHasher\PasswordHasherInterface;

final class OmekaPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_DEFAULT);
    }

    public function verify(string $hashedPassword, string $plainPassword): bool
    {
        return true;
    }

    public function needsRehash(string $hashedPassword): bool
    {
        return false;
    }
}
