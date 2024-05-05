<?php

declare(strict_types=1);

namespace App\Auth;

use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final readonly class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public function __construct(private string $identifier)
    {
    }

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return ['ROLE_USER'];
    }

    public function getPassword(): string
    {
        return '';
    }

    public function eraseCredentials(): void
    {
    }
}
