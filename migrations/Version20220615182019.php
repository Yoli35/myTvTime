<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220615182019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE youtube_channel CHANGE channel_id youtube_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE youtube_video DROP channel_title, CHANGE channel_id channel_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE youtube_video ADD CONSTRAINT FK_AE4DCDC972F5A1AA FOREIGN KEY (channel_id) REFERENCES youtube_channel (id)');
        $this->addSql('CREATE INDEX IDX_AE4DCDC972F5A1AA ON youtube_video (channel_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE youtube_channel CHANGE youtube_id channel_id VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE youtube_video DROP FOREIGN KEY FK_AE4DCDC972F5A1AA');
        $this->addSql('DROP INDEX IDX_AE4DCDC972F5A1AA ON youtube_video');
        $this->addSql('ALTER TABLE youtube_video ADD channel_title VARCHAR(255) NOT NULL, CHANGE channel_id channel_id VARCHAR(32) NOT NULL');
    }
}
