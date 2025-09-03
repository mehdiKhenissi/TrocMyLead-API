<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240708090939 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE enterprise (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(64) NOT NULL, address VARCHAR(128) NOT NULL, postal_code INT NOT NULL, city VARCHAR(128) NOT NULL, country VARCHAR(64) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', disabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', enabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', siren INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE leads (id INT AUTO_INCREMENT NOT NULL, enterprise_id INT NOT NULL, firstname VARCHAR(64) NOT NULL, name VARCHAR(64) NOT NULL, address VARCHAR(128) NOT NULL, postal_code VARCHAR(6) NOT NULL, city VARCHAR(64) NOT NULL, country VARCHAR(64) NOT NULL, phone INT NOT NULL, email VARCHAR(128) NOT NULL, status VARCHAR(16) NOT NULL, commentary LONGTEXT DEFAULT NULL, pricing_to_seller NUMERIC(10, 2) NOT NULL, pricing_to_tpl NUMERIC(10, 2) NOT NULL, pricing_to_increase NUMERIC(10, 2) NOT NULL, min_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', max_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', disabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', validated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', activity VARCHAR(32) DEFAULT NULL, INDEX IDX_17904552A97D1AC3 (enterprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE litige (id INT AUTO_INCREMENT NOT NULL, lead_id INT DEFAULT NULL, commentary LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', closed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', status VARCHAR(32) DEFAULT NULL, UNIQUE INDEX UNIQ_EEE9D46D55458D (lead_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE litige_step (id INT AUTO_INCREMENT NOT NULL, litige_id INT DEFAULT NULL, step INT NOT NULL, commentary LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B0C626581ACCC76A (litige_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE sell (id INT AUTO_INCREMENT NOT NULL, buyer_enterprise_id INT NOT NULL, lead_id INT NOT NULL, pricing NUMERIC(10, 2) DEFAULT NULL, id_stripe VARCHAR(255) DEFAULT NULL, id_charge VARCHAR(255) DEFAULT NULL, statut VARCHAR(32) DEFAULT NULL, invoice_id VARCHAR(255) DEFAULT NULL, invoice_num VARCHAR(255) DEFAULT NULL, invoice_link VARCHAR(255) DEFAULT NULL, stripe_payment_id VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_9B9ED07DD7F4F225 (buyer_enterprise_id), UNIQUE INDEX UNIQ_9B9ED07D55458D (lead_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, enterprise_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, firstname VARCHAR(64) DEFAULT NULL, name VARCHAR(64) NOT NULL, phone INT NOT NULL, code_validation VARCHAR(64) NOT NULL, enabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', disabled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', main SMALLINT DEFAULT NULL, stripe_customer_id VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), INDEX IDX_8D93D649A97D1AC3 (enterprise_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE leads ADD CONSTRAINT FK_17904552A97D1AC3 FOREIGN KEY (enterprise_id) REFERENCES enterprise (id)');
        $this->addSql('ALTER TABLE litige ADD CONSTRAINT FK_EEE9D46D55458D FOREIGN KEY (lead_id) REFERENCES leads (id)');
        $this->addSql('ALTER TABLE litige_step ADD CONSTRAINT FK_B0C626581ACCC76A FOREIGN KEY (litige_id) REFERENCES litige (id)');
        $this->addSql('ALTER TABLE sell ADD CONSTRAINT FK_9B9ED07DD7F4F225 FOREIGN KEY (buyer_enterprise_id) REFERENCES enterprise (id)');
        $this->addSql('ALTER TABLE sell ADD CONSTRAINT FK_9B9ED07D55458D FOREIGN KEY (lead_id) REFERENCES leads (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A97D1AC3 FOREIGN KEY (enterprise_id) REFERENCES enterprise (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE leads DROP FOREIGN KEY FK_17904552A97D1AC3');
        $this->addSql('ALTER TABLE litige DROP FOREIGN KEY FK_EEE9D46D55458D');
        $this->addSql('ALTER TABLE litige_step DROP FOREIGN KEY FK_B0C626581ACCC76A');
        $this->addSql('ALTER TABLE sell DROP FOREIGN KEY FK_9B9ED07DD7F4F225');
        $this->addSql('ALTER TABLE sell DROP FOREIGN KEY FK_9B9ED07D55458D');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D649A97D1AC3');
        $this->addSql('DROP TABLE enterprise');
        $this->addSql('DROP TABLE leads');
        $this->addSql('DROP TABLE litige');
        $this->addSql('DROP TABLE litige_step');
        $this->addSql('DROP TABLE sell');
        $this->addSql('DROP TABLE user');
    }
}
