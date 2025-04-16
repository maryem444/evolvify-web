<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250327173621 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE abonnement DROP FOREIGN KEY id_employe_fk');
        $this->addSql('ALTER TABLE absence DROP FOREIGN KEY id_employe_fkabsence');
        $this->addSql('ALTER TABLE liste_offres DROP FOREIGN KEY id_offre_fko');
        $this->addSql('ALTER TABLE projet_employe DROP FOREIGN KEY fk_employe');
        $this->addSql('ALTER TABLE projet_employe DROP FOREIGN KEY fk_projet');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY id_projet_fk');
        $this->addSql('ALTER TABLE tache DROP FOREIGN KEY id_employe_fktache');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY id_employe_fkt');
        $this->addSql('ALTER TABLE trajet DROP FOREIGN KEY id_moyen_fk');
        $this->addSql('ALTER TABLE tt DROP FOREIGN KEY id_employe_fktt');
        $this->addSql('DROP TABLE abonnement');
        $this->addSql('DROP TABLE absence');
        $this->addSql('DROP TABLE congé');
        $this->addSql('DROP TABLE liste_offres');
        $this->addSql('DROP TABLE moyentransport');
        $this->addSql('DROP TABLE offre');
        $this->addSql('DROP TABLE projet_employe');
        $this->addSql('DROP TABLE tache');
        $this->addSql('DROP TABLE trajet');
        $this->addSql('DROP TABLE tt');
        $this->addSql('DROP TABLE users');
        $this->addSql('ALTER TABLE projet MODIFY id_projet INT NOT NULL');
        $this->addSql('DROP INDEX `primary` ON projet');
        $this->addSql('ALTER TABLE projet CHANGE status status VARCHAR(255) NOT NULL, CHANGE uploaded_files uploaded_files LONGBLOB DEFAULT NULL, CHANGE id_projet id INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE projet ADD PRIMARY KEY (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE abonnement (id_Ab INT AUTO_INCREMENT NOT NULL, type_Ab VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_debut DATE NOT NULL, date_exp DATE NOT NULL, prix DOUBLE PRECISION NOT NULL, id_employe INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fk (id_employe), PRIMARY KEY(id_Ab)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE absence (id_absence INT AUTO_INCREMENT NOT NULL, status ENUM(\'ABSENT\', \'PRESENT\', \'EN_CONGE\', \'\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date DATE NOT NULL, id_employe INT NOT NULL, INDEX id_employe_fkabsence (id_employe), PRIMARY KEY(id_absence)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE congé (id_conge INT AUTO_INCREMENT NOT NULL, leave_start DATE NOT NULL, leave_end DATE NOT NULL, number_of_days INT NOT NULL, status ENUM(\'REFUSE\', \'ACCEPTE\', \'EN_COURS\', \'\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, id_employe INT NOT NULL, type ENUM(\'conge\', \'TT\', \'autorisation\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, reason ENUM(\'CONGE_PAYE\', \'CONGE_SANS_SOLDE\', \'MALADIE\', \'MATERNITE\', \'FORMATION\', \'AMMENAGEMENT\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fkconge (id_employe), PRIMARY KEY(id_conge)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE liste_offres (id_liste_offres INT AUTO_INCREMENT NOT NULL, id_condidat INT NOT NULL, id_offre INT NOT NULL, status ENUM(\'en_cours\', \'accepte\', \'refuse\') CHARACTER SET utf8mb4 DEFAULT \'en_cours\' NOT NULL COLLATE `utf8mb4_general_ci`, date_postulation DATE NOT NULL, INDEX id_employe_fko (id_condidat), INDEX id_offre_fko (id_offre), PRIMARY KEY(id_liste_offres)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE moyentransport (id_moyen INT AUTO_INCREMENT NOT NULL, type_moyen VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, capacité INT NOT NULL, immatriculation INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id_moyen)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE offre (id_offre INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_publication DATE NOT NULL, date_expiration DATE NOT NULL, status ENUM(\'active\', \'expire\', \'attend\') CHARACTER SET utf8mb4 DEFAULT \'attend\' NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id_offre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE projet_employe (projet_id INT NOT NULL, employe_id INT NOT NULL, INDEX fk_employe (employe_id), INDEX IDX_7A2E8EC8C18272 (projet_id), PRIMARY KEY(projet_id, employe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tache (id_tache INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status ENUM(\'TO_DO\', \'IN_PROGRESS\', \'DONE\', \'CANCELED\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATE NOT NULL, id_employe INT NOT NULL, id_projet INT NOT NULL, priority ENUM(\'HIGH\', \'MEDIUM\', \'LOW\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fktache (id_employe), INDEX id_projet_fk (id_projet), PRIMARY KEY(id_tache)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE trajet (id_T INT AUTO_INCREMENT NOT NULL, point_dep VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, point_arr VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, distance DOUBLE PRECISION NOT NULL, durée_estimé TIME NOT NULL, id_moyen INT NOT NULL, id_employe INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fkt (id_employe), INDEX id_moyen_fk (id_moyen), PRIMARY KEY(id_T)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE tt (id_tt INT AUTO_INCREMENT NOT NULL, leave_start DATE NOT NULL, leave_end DATE NOT NULL, number_of_days INT NOT NULL, status ENUM(\'REFUSE\', \'ACCEPTE\', \'EN_COURS\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, id_employe INT NOT NULL, INDEX id_employe_fktt (id_employe), PRIMARY KEY(id_tt)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE users (id_employe INT AUTO_INCREMENT NOT NULL, firstname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lastname VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, profilePhoto VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, birthdayDate DATE DEFAULT NULL, joiningDate DATE DEFAULT CURRENT_DATE, role ENUM(\'RESPONSABLE_RH\', \'CHEF_PROJET\', \'EMPLOYEE\', \'CONDIDAT\') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, tt_restants INT DEFAULT NULL, conge_restant INT DEFAULT NULL, uploaded_cv BLOB DEFAULT NULL, num_tel INT DEFAULT NULL, gender ENUM(\'HOMME\', \'FEMME\') CHARACTER SET utf8mb4 DEFAULT \'HOMME\' NOT NULL COLLATE `utf8mb4_general_ci`, birthdate_edited TINYINT(1) DEFAULT 0, first_login TINYINT(1) DEFAULT 1, UNIQUE INDEX unique_email (email), PRIMARY KEY(id_employe)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE abonnement ADD CONSTRAINT id_employe_fk FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE absence ADD CONSTRAINT id_employe_fkabsence FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE liste_offres ADD CONSTRAINT id_offre_fko FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE projet_employe ADD CONSTRAINT fk_employe FOREIGN KEY (employe_id) REFERENCES users (id_employe) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE projet_employe ADD CONSTRAINT fk_projet FOREIGN KEY (projet_id) REFERENCES projet (id_projet) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT id_projet_fk FOREIGN KEY (id_projet) REFERENCES projet (id_projet) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tache ADD CONSTRAINT id_employe_fktache FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT id_employe_fkt FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE trajet ADD CONSTRAINT id_moyen_fk FOREIGN KEY (id_moyen) REFERENCES moyentransport (id_moyen) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE tt ADD CONSTRAINT id_employe_fktt FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE');
        $this->addSql('ALTER TABLE projet MODIFY id INT NOT NULL');
        $this->addSql('DROP INDEX `PRIMARY` ON projet');
        $this->addSql('ALTER TABLE projet CHANGE status status ENUM(\'IN_PROGRESS\', \'COMPLETED\') NOT NULL, CHANGE uploaded_files uploaded_files BLOB DEFAULT NULL, CHANGE id id_projet INT AUTO_INCREMENT NOT NULL');
        $this->addSql('ALTER TABLE projet ADD PRIMARY KEY (id_projet)');
    }
}
