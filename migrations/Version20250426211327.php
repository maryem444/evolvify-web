<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250426211327 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement DROP FOREIGN KEY id_employe_fk
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE liste_offres DROP FOREIGN KEY id_offre_fko
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tache DROP FOREIGN KEY id_employe_fktache
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tache DROP FOREIGN KEY id_projet_fk
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet DROP FOREIGN KEY id_employe_fkt
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet DROP FOREIGN KEY id_moyen_fk
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tt DROP FOREIGN KEY id_employe_fktt
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE abonnement
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE absence
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE congé
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE liste_offres
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE moyentransport
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE offre
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projet
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE projet_employe
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tache
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE trajet
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE tt
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data DROP FOREIGN KEY user_biometric_data_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data DROP FOREIGN KEY user_biometric_data_ibfk_1
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data CHANGE id_employe id_employe INT DEFAULT NULL, CHANGE enabled enabled TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data ADD CONSTRAINT FK_4D23DE4D26997AC9 FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX user_biometric_data_ibfk_1 ON user_biometric_data
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_4D23DE4D26997AC9 ON user_biometric_data (id_employe)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data ADD CONSTRAINT user_biometric_data_ibfk_1 FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD face_image_filename VARCHAR(255) DEFAULT NULL, CHANGE joiningDate joiningDate DATE DEFAULT NULL, CHANGE role role ENUM('RESPONSABLE_RH','CHEF_PROJET','EMPLOYEE','CONDIDAT'), CHANGE gender gender ENUM('HOMME','FEMME'), CHANGE birthdate_edited birthdate_edited TINYINT(1) DEFAULT NULL, CHANGE first_login first_login TINYINT(1) DEFAULT NULL, CHANGE facial_data facial_data LONGTEXT DEFAULT NULL, CHANGE facial_auth_enabled facial_auth_enabled TINYINT(1) NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX unique_email ON users
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX UNIQ_1483A5E9E7927C74 ON users (email)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE abonnement (id_Ab INT AUTO_INCREMENT NOT NULL, type_Ab VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_debut DATE NOT NULL, date_exp DATE NOT NULL, prix DOUBLE PRECISION NOT NULL, id_employe INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fk (id_employe), PRIMARY KEY(id_Ab)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE absence (id_absence INT AUTO_INCREMENT NOT NULL, status ENUM('ABSENT', 'PRESENT', 'EN_CONGE', '') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date DATE NOT NULL, id_employe INT NOT NULL, INDEX id_employe_fkabsence (id_employe), PRIMARY KEY(id_absence)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE congé (id_conge INT AUTO_INCREMENT NOT NULL, leave_start DATE NOT NULL, leave_end DATE NOT NULL, number_of_days INT NOT NULL, status ENUM('REFUSE', 'ACCEPTE', 'EN_COURS', '') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, id_employe INT NOT NULL, type ENUM('conge', 'TT', 'autorisation') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, reason ENUM('CONGE_PAYE', 'CONGE_SANS_SOLDE', 'MALADIE', 'MATERNITE', 'FORMATION', 'AMMENAGEMENT') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fkconge (id_employe), PRIMARY KEY(id_conge)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE liste_offres (id_liste_offres INT AUTO_INCREMENT NOT NULL, id_condidat INT NOT NULL, id_offre INT NOT NULL, status ENUM('en_cours', 'accepte', 'refuse') CHARACTER SET utf8mb4 DEFAULT 'en_cours' NOT NULL COLLATE `utf8mb4_general_ci`, date_postulation DATE NOT NULL, INDEX id_offre_fko (id_offre), INDEX id_employe_fko (id_condidat), PRIMARY KEY(id_liste_offres)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE moyentransport (id_moyen INT AUTO_INCREMENT NOT NULL, type_moyen VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, capacité INT NOT NULL, immatriculation INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id_moyen)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE offre (id_offre INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, date_publication DATE NOT NULL, date_expiration DATE NOT NULL, status ENUM('active', 'expire', 'attend') CHARACTER SET utf8mb4 DEFAULT 'attend' NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id_offre)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projet (id_projet INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status ENUM('IN_PROGRESS', 'COMPLETED') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, end_date DATE NOT NULL, starter_at DATE NOT NULL, abbreviation VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, uploaded_files VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(id_projet)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE projet_employe (projet_id INT NOT NULL, employe_id INT NOT NULL, INDEX fk_employe (employe_id), PRIMARY KEY(projet_id, employe_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tache (id_tache INT AUTO_INCREMENT NOT NULL, description VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, status ENUM('TO_DO', 'IN_PROGRESS', 'DONE', 'CANCELED') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, created_at DATE NOT NULL, id_employe INT NOT NULL, id_projet INT NOT NULL, priority ENUM('HIGH', 'MEDIUM', 'LOW') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, location VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fktache (id_employe), INDEX id_projet_fk (id_projet), PRIMARY KEY(id_tache)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE trajet (id_T INT AUTO_INCREMENT NOT NULL, point_dep VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, point_arr VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, distance DOUBLE PRECISION NOT NULL, durée_estimé TIME NOT NULL, id_moyen INT NOT NULL, id_employe INT NOT NULL, status VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, INDEX id_employe_fkt (id_employe), INDEX id_moyen_fk (id_moyen), PRIMARY KEY(id_T)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            CREATE TABLE tt (id_tt INT AUTO_INCREMENT NOT NULL, leave_start DATE NOT NULL, leave_end DATE NOT NULL, number_of_days INT NOT NULL, status ENUM('REFUSE', 'ACCEPTE', 'EN_COURS') CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, id_employe INT NOT NULL, INDEX id_employe_fktt (id_employe), PRIMARY KEY(id_tt)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE abonnement ADD CONSTRAINT id_employe_fk FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE liste_offres ADD CONSTRAINT id_offre_fko FOREIGN KEY (id_offre) REFERENCES offre (id_offre) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tache ADD CONSTRAINT id_employe_fktache FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tache ADD CONSTRAINT id_projet_fk FOREIGN KEY (id_projet) REFERENCES projet (id_projet) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet ADD CONSTRAINT id_employe_fkt FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE trajet ADD CONSTRAINT id_moyen_fk FOREIGN KEY (id_moyen) REFERENCES moyentransport (id_moyen) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE tt ADD CONSTRAINT id_employe_fktt FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP face_image_filename, CHANGE joiningDate joiningDate DATE DEFAULT CURRENT_DATE, CHANGE role role ENUM('RESPONSABLE_RH', 'CHEF_PROJET', 'EMPLOYEE', 'CONDIDAT') NOT NULL, CHANGE gender gender ENUM('HOMME', 'FEMME') DEFAULT 'HOMME' NOT NULL, CHANGE birthdate_edited birthdate_edited TINYINT(1) DEFAULT 0, CHANGE first_login first_login TINYINT(1) DEFAULT 1, CHANGE facial_data facial_data TEXT DEFAULT NULL, CHANGE facial_auth_enabled facial_auth_enabled TINYINT(1) DEFAULT 0 NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX uniq_1483a5e9e7927c74 ON users
        SQL);
        $this->addSql(<<<'SQL'
            CREATE UNIQUE INDEX unique_email ON users (email)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data DROP FOREIGN KEY FK_4D23DE4D26997AC9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data DROP FOREIGN KEY FK_4D23DE4D26997AC9
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data CHANGE enabled enabled TINYINT(1) DEFAULT 1 NOT NULL, CHANGE id_employe id_employe INT NOT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data ADD CONSTRAINT user_biometric_data_ibfk_1 FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON UPDATE CASCADE ON DELETE CASCADE
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX idx_4d23de4d26997ac9 ON user_biometric_data
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX user_biometric_data_ibfk_1 ON user_biometric_data (id_employe)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE user_biometric_data ADD CONSTRAINT FK_4D23DE4D26997AC9 FOREIGN KEY (id_employe) REFERENCES users (id_employe) ON DELETE CASCADE
        SQL);
    }
}
