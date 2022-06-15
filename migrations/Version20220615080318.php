<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220615080318 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE youtube ADD user_id INT NOT NULL, ADD category_id INT NOT NULL, ADD channel_id VARCHAR(32) NOT NULL, ADD channel_title VARCHAR(255) NOT NULL, ADD default_audio_language VARCHAR(8) NOT NULL, ADD description LONGTEXT DEFAULT NULL, ADD published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD title VARCHAR(255) NOT NULL, ADD thumbnail_default_path VARCHAR(255) NOT NULL, ADD thumbnail_medium_path VARCHAR(255) NOT NULL, ADD thumbnail_high_path VARCHAR(255) NOT NULL, ADD thumbnail_standard_path VARCHAR(255) NOT NULL, ADD thumbnail_maxres_path VARCHAR(255) NOT NULL, ADD localized_description LONGTEXT DEFAULT NULL, ADD localized_title VARCHAR(255) DEFAULT NULL, ADD content_definition VARCHAR(8) NOT NULL, ADD content_dimension VARCHAR(8) NOT NULL, ADD content_duration VARCHAR(16) NOT NULL, ADD content_projection VARCHAR(16) NOT NULL, DROP published');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE youtube ADD published DATE DEFAULT NULL, DROP user_id, DROP category_id, DROP channel_id, DROP channel_title, DROP default_audio_language, DROP description, DROP published_at, DROP title, DROP thumbnail_default_path, DROP thumbnail_medium_path, DROP thumbnail_high_path, DROP thumbnail_standard_path, DROP thumbnail_maxres_path, DROP localized_description, DROP localized_title, DROP content_definition, DROP content_dimension, DROP content_duration, DROP content_projection');
    }
}
