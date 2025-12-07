<?php

namespace App\Security;

use Symfony\Component\Security\Core\User\UserInterface;

class JWTUser implements UserInterface
{
    private string $email;
    private array $roles;

    public function __construct(string $email, string $role)
    {
        $this->email = $email;
        $this->roles = [$this->mapRole($role)];
    }

    private function mapRole(string $role): string
    {
        return match ($role) {
            'Schueler' => 'ROLE_SCHUELER',
            'Lehrer'   => 'ROLE_LEHRER',
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
        // Nichts zu löschen, keine Passwörter gespeichert
    }
}
