<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260119195501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Benotungsarten CHANGE gewichtung gewichtung NUMERIC(5, 2) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE Kurs_Einstellungen RENAME INDEX fk_971174dd2caafbec TO IDX_971174DD2CAAFBEC');
        $this->addSql('ALTER TABLE Kurs_Schueler RENAME INDEX fk_ks_schueler TO IDX_A0D43F329AC0A64E');
        $this->addSql('ALTER TABLE Kurse RENAME INDEX fk_kurse_fach TO IDX_DB5FCD37FA2B40D');
        $this->addSql('ALTER TABLE Kurse RENAME INDEX fk_kurse_lehrer TO IDX_DB5FCD373302EA81');
        $this->addSql('ALTER TABLE Kurse RENAME INDEX fk_kurse_klasse TO IDX_DB5FCD3734860711');
        $this->addSql('ALTER TABLE Lehrer RENAME INDEX fk_lehrer_m365 TO IDX_97EC7FA9FE570A08');
        $this->addSql('ALTER TABLE Nachrichten CHANGE inhalt inhalt LONGTEXT NOT NULL, CHANGE erstellt_am erstellt_am DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE Nachrichten RENAME INDEX fk_nachrichten_kurs TO IDX_2208B4F42CAAFBEC');
        $this->addSql('ALTER TABLE Nachrichten RENAME INDEX fk_nachrichten_schueler TO IDX_2208B4F4D0BD40D2');
        $this->addSql('ALTER TABLE Nachrichten_Status CHANGE gelesen gelesen TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE Nachrichten_Status RENAME INDEX fk_ns_schueler TO IDX_AEBEE4749AC0A64E');
        $this->addSql('ALTER TABLE Schueler RENAME INDEX fk_schueler_klasse TO IDX_3AF4253B34860711');
        $this->addSql('ALTER TABLE Schueler RENAME INDEX ms365usr_id TO IDX_3AF4253BFE570A08');
        $this->addSql('ALTER TABLE tbl_Microsoft365_User CHANGE erstellungszeitpunkt erstellungszeitpunkt DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE Nachrichten CHANGE inhalt inhalt TEXT NOT NULL, CHANGE erstellt_am erstellt_am DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE Nachrichten RENAME INDEX idx_2208b4f42caafbec TO fk_nachrichten_kurs');
        $this->addSql('ALTER TABLE Nachrichten RENAME INDEX idx_2208b4f4d0bd40d2 TO fk_nachrichten_schueler');
        $this->addSql('ALTER TABLE Lehrer RENAME INDEX idx_97ec7fa9fe570a08 TO fk_lehrer_m365');
        $this->addSql('ALTER TABLE Benotungsarten CHANGE gewichtung gewichtung NUMERIC(5, 2) DEFAULT \'0.00\' NOT NULL');
        $this->addSql('ALTER TABLE Schueler RENAME INDEX idx_3af4253b34860711 TO fk_schueler_klasse');
        $this->addSql('ALTER TABLE Schueler RENAME INDEX idx_3af4253bfe570a08 TO ms365usr_id');
        $this->addSql('ALTER TABLE tbl_Microsoft365_User CHANGE erstellungszeitpunkt erstellungszeitpunkt DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE Kurs_Einstellungen RENAME INDEX idx_971174dd2caafbec TO FK_971174DD2CAAFBEC');
        $this->addSql('ALTER TABLE Nachrichten_Status CHANGE gelesen gelesen TINYINT(1) DEFAULT 0');
        $this->addSql('ALTER TABLE Nachrichten_Status RENAME INDEX idx_aebee4749ac0a64e TO fk_ns_schueler');
        $this->addSql('ALTER TABLE Kurse RENAME INDEX idx_db5fcd3734860711 TO fk_kurse_klasse');
        $this->addSql('ALTER TABLE Kurse RENAME INDEX idx_db5fcd373302ea81 TO fk_kurse_lehrer');
        $this->addSql('ALTER TABLE Kurse RENAME INDEX idx_db5fcd37fa2b40d TO fk_kurse_fach');
        $this->addSql('ALTER TABLE Kurs_Schueler RENAME INDEX idx_a0d43f329ac0a64e TO fk_ks_schueler');
    }
}
