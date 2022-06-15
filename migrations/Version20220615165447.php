<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220615165447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE youtube_channel (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, custom_url VARCHAR(255) DEFAULT NULL, published_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', thumbnail_default_url VARCHAR(255) DEFAULT NULL, thumbnail_medium_url VARCHAR(255) DEFAULT NULL, thumbnail_high_url VARCHAR(255) DEFAULT NULL, localized_title VARCHAR(255) DEFAULT NULL, localized_description LONGTEXT DEFAULT NULL, country VARCHAR(16) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE youtube_channel_thumbnail_dimension (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, height INT DEFAULT NULL, width INT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE youtube_video (id INT AUTO_INCREMENT NOT NULL, link VARCHAR(255) NOT NULL, user_id INT NOT NULL, category_id INT NOT NULL, channel_id VARCHAR(32) NOT NULL, channel_title VARCHAR(255) NOT NULL, default_audio_language VARCHAR(8) DEFAULT NULL, description LONGTEXT DEFAULT NULL, published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', title VARCHAR(255) NOT NULL, thumbnail_default_path VARCHAR(255) DEFAULT NULL, thumbnail_medium_path VARCHAR(255) DEFAULT NULL, thumbnail_high_path VARCHAR(255) DEFAULT NULL, thumbnail_standard_path VARCHAR(255) DEFAULT NULL, thumbnail_maxres_path VARCHAR(255) DEFAULT NULL, localized_description LONGTEXT DEFAULT NULL, localized_title VARCHAR(255) DEFAULT NULL, content_definition VARCHAR(8) NOT NULL, content_dimension VARCHAR(8) NOT NULL, content_duration INT NOT NULL, content_projection VARCHAR(16) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE youtube_video_thumbnail_dimension (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(16) NOT NULL, height INT NOT NULL, width INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('DROP TABLE youtube');
        $this->addSql('DROP TABLE youtube_thumbnail_dimension');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE youtube (id INT AUTO_INCREMENT NOT NULL, link VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, user_id INT NOT NULL, category_id INT NOT NULL, channel_id VARCHAR(32) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, channel_title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, default_audio_language VARCHAR(8) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, published_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, thumbnail_default_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, thumbnail_medium_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, thumbnail_high_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, thumbnail_standard_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, thumbnail_maxres_path VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, localized_description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, localized_title VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, content_definition VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content_dimension VARCHAR(8) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, content_duration INT NOT NULL, content_projection VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE youtube_thumbnail_dimension (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, height INT NOT NULL, width INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE youtube_channel');
        $this->addSql('DROP TABLE youtube_channel_thumbnail_dimension');
        $this->addSql('DROP TABLE youtube_video');
        $this->addSql('DROP TABLE youtube_video_thumbnail_dimension');
    }
}
