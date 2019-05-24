<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190522175344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE programs ADD description VARCHAR(255) NOT NULL, ADD images VARCHAR(255) NOT NULL, ADD users VARCHAR(255) NOT NULL, DROP descrition, DROP image, DROP users_id');
        $this->addSql('ALTER TABLE users CHANGE programs_id programs VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE programs ADD descrition VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ADD image VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, ADD users_id VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci, DROP description, DROP images, DROP users');
        $this->addSql('ALTER TABLE users CHANGE programs programs_id VARCHAR(255) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
