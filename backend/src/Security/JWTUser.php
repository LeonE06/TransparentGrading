<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class JWTUser implements UserInterface
{
    private string $email;
    private array $roles;

    public function __construct(string $email, array|string $roles)
    {
        $this->email = $email;

        $roleNames = is_array($roles) ? $roles : [$roles];
        $mappedRoles = array_map([$this, 'mapRole'], $roleNames);
        $mappedRoles[] = 'ROLE_USER';

        $this->roles = array_values(array_unique($mappedRoles));
    }

    private function mapRole(string $role): string
    {
        return match ($role) {
            'Schueler' => 'ROLE_SCHUELER',
            'Lehrer'   => 'ROLE_LEHRER',
            'Admin'    => 'ROLE_ADMIN',
            default    => 'ROLE_USER',
        };
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function eraseCredentials(): void
    {
    }
}
