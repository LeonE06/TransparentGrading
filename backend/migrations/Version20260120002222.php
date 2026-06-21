<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260120002222 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix defaults for Nachrichten.erstellt_am and Benotungsarten.gewichtung to match Doctrine mapping';
    }

    public function up(Schema $schema): void
    {
        // 🔒 Finaler Fix: exakt das setzen, was Doctrine erwartet
        $this->addSql(
            "ALTER TABLE Nachrichten 
             CHANGE erstellt_am erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP"
        );

        $this->addSql(
            "ALTER TABLE Benotungsarten 
             CHANGE gewichtung gewichtung NUMERIC(5, 2) NOT NULL DEFAULT '0'"
        );
    }

    public function down(Schema $schema): void
    {
        // ❗ Absichtlich leer
        // Diese Migration behebt einen Schema-Mismatch.
        // Ein Down würde den Fehler wieder einführen.
    }
}
