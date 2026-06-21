<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260120001108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Aufgaben ADD kommentar LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE Aufgaben_Bewertung ADD datum DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE Benotungsarten CHANGE gewichtung gewichtung NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('DROP INDEX `primary` ON Einstellungen');
        $this->addSql('ALTER TABLE Einstellungen CHANGE id schueler_id INT NOT NULL');
        $this->addSql('ALTER TABLE Einstellungen ADD CONSTRAINT FK_164685279AC0A64E FOREIGN KEY (schueler_id) REFERENCES Schueler (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE Einstellungen ADD PRIMARY KEY (schueler_id)');
        $this->addSql('ALTER TABLE Nachrichten CHANGE erstellt_am erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Nachrichten CHANGE erstellt_am erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL');
        $this->addSql('ALTER TABLE Einstellungen DROP FOREIGN KEY FK_164685279AC0A64E');
        $this->addSql('DROP INDEX `PRIMARY` ON Einstellungen');
        $this->addSql('ALTER TABLE Einstellungen CHANGE schueler_id id INT NOT NULL');
        $this->addSql('ALTER TABLE Einstellungen ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE Benotungsarten CHANGE gewichtung gewichtung NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE Aufgaben_Bewertung DROP datum');
        $this->addSql('ALTER TABLE Aufgaben DROP kommentar');
    }
}
