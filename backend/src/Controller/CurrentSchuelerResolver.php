<?php
namespace App\Controller;

use App\Entity\Schueler;
use App\Entity\Microsoft365User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Security;

class CurrentSchuelerResolver
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em
    ) {}

    public function get(): ?Schueler
    {
        $user = $this->security->getUser();
        if (!$user) return null;

        $email = $user->getUserIdentifier();

        $m365 = $this->em->getRepository(Microsoft365User::class)
            ->findOneBy(['email' => $email]);

        if (!$m365) return null;

        return $this->em->getRepository(Schueler::class)
            ->findOneBy(['ms365User' => $m365]);
    }
}
