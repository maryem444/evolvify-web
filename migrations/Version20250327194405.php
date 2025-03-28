<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250327194405 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet ADD id INT AUTO_INCREMENT NOT NULL, DROP id_projet, CHANGE status status VARCHAR(255) NOT NULL, CHANGE uploaded_files uploaded_files LONGBLOB DEFAULT NULL, ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE projet MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON projet');
        $this->addSql('ALTER TABLE projet ADD id_projet INT NOT NULL, DROP id, CHANGE status status ENUM(\'IN_PROGRESS\', \'COMPLETED\') NOT NULL, CHANGE uploaded_files uploaded_files BLOB DEFAULT NULL');
    }
}
