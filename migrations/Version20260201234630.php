<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260201234630 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE api_key (id VARCHAR(32) NOT NULL, label VARCHAR(255) NOT NULL, credential_hash VARCHAR(60) NOT NULL, last_ip VARBINARY(16) DEFAULT NULL, last_accessed DATETIME DEFAULT NULL, created DATETIME NOT NULL, owner_id INT NOT NULL, INDEX IDX_C912ED9D7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE asset (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, media_type VARCHAR(255) NOT NULL, storage_id VARCHAR(190) NOT NULL, extension VARCHAR(255) DEFAULT NULL, alt_text LONGTEXT DEFAULT NULL, owner_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_2AF5A5C5CC5DB90 (storage_id), INDEX IDX_2AF5A5C7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE fulltext_search (id INT NOT NULL, resource VARCHAR(190) NOT NULL, is_public TINYINT NOT NULL, title LONGTEXT DEFAULT NULL, text LONGTEXT DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_AA31FE4A7E3C61F9 (owner_id), PRIMARY KEY (id, resource)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE item (primary_media_id INT DEFAULT NULL, id INT NOT NULL, INDEX IDX_1F1B251ECBE0B084 (primary_media_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE item_item_set (item_id INT NOT NULL, item_set_id INT NOT NULL, INDEX IDX_6D0C9625126F525E (item_id), INDEX IDX_6D0C9625960278D7 (item_set_id), PRIMARY KEY (item_id, item_set_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE item_site (item_id INT NOT NULL, site_id INT NOT NULL, INDEX IDX_A1734D1F126F525E (item_id), INDEX IDX_A1734D1FF6BD1646 (site_id), PRIMARY KEY (item_id, site_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE item_set (is_open TINYINT NOT NULL, id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE job (id INT AUTO_INCREMENT NOT NULL, pid VARCHAR(255) DEFAULT NULL, status VARCHAR(255) DEFAULT NULL, class VARCHAR(255) NOT NULL, args JSON DEFAULT NULL, log LONGTEXT DEFAULT NULL, started DATETIME NOT NULL, ended DATETIME DEFAULT NULL, owner_id INT DEFAULT NULL, INDEX IDX_FBD8E0F87E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE media (ingester VARCHAR(255) NOT NULL, renderer VARCHAR(255) NOT NULL, data JSON DEFAULT NULL, source LONGTEXT DEFAULT NULL, media_type VARCHAR(190) DEFAULT NULL, storage_id VARCHAR(190) DEFAULT NULL, extension VARCHAR(255) DEFAULT NULL, sha256 CHAR(64) DEFAULT NULL, size BIGINT DEFAULT NULL, has_original TINYINT NOT NULL, has_thumbnails TINYINT NOT NULL, position INT DEFAULT NULL, lang VARCHAR(190) DEFAULT NULL, alt_text LONGTEXT DEFAULT NULL, item_id INT NOT NULL, id INT NOT NULL, UNIQUE INDEX UNIQ_6A2CA10C5CC5DB90 (storage_id), INDEX IDX_6A2CA10C126F525E (item_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE migration (version VARCHAR(16) NOT NULL, PRIMARY KEY (version)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE module (id VARCHAR(190) NOT NULL, is_active TINYINT NOT NULL, version VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE password_creation (id VARCHAR(32) NOT NULL COLLATE `utf8mb4_bin`, created DATETIME NOT NULL, activate TINYINT NOT NULL, user_id INT NOT NULL, UNIQUE INDEX UNIQ_C77917B4A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE property (id INT AUTO_INCREMENT NOT NULL, local_name VARCHAR(190) NOT NULL COLLATE `utf8mb4_bin`, label VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, owner_id INT DEFAULT NULL, vocabulary_id INT NOT NULL, INDEX IDX_8BF21CDE7E3C61F9 (owner_id), INDEX IDX_8BF21CDEAD0E05F6 (vocabulary_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE resource (id INT AUTO_INCREMENT NOT NULL, title LONGTEXT DEFAULT NULL, is_public TINYINT NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, owner_id INT DEFAULT NULL, resource_class_id INT DEFAULT NULL, resource_template_id INT DEFAULT NULL, thumbnail_id INT DEFAULT NULL, resource_type VARCHAR(255) NOT NULL, INDEX IDX_BC91F4167E3C61F9 (owner_id), INDEX IDX_BC91F416448CC1BD (resource_class_id), INDEX IDX_BC91F41616131EA (resource_template_id), INDEX IDX_BC91F416FDFF2E92 (thumbnail_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE resource_class (id INT AUTO_INCREMENT NOT NULL, local_name VARCHAR(190) NOT NULL COLLATE `utf8mb4_bin`, label VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, owner_id INT DEFAULT NULL, vocabulary_id INT NOT NULL, INDEX IDX_C6F063AD7E3C61F9 (owner_id), INDEX IDX_C6F063ADAD0E05F6 (vocabulary_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE resource_template (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(190) NOT NULL, owner_id INT DEFAULT NULL, resource_class_id INT DEFAULT NULL, title_property_id INT DEFAULT NULL, description_property_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_39ECD52EEA750E8 (label), INDEX IDX_39ECD52E7E3C61F9 (owner_id), INDEX IDX_39ECD52E448CC1BD (resource_class_id), INDEX IDX_39ECD52E724734A3 (title_property_id), INDEX IDX_39ECD52EB84E0D1D (description_property_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE resource_template_property (id INT AUTO_INCREMENT NOT NULL, alternate_label VARCHAR(255) DEFAULT NULL, alternate_comment LONGTEXT DEFAULT NULL, position INT DEFAULT NULL, data_type JSON DEFAULT NULL, is_required TINYINT NOT NULL, is_private TINYINT NOT NULL, default_lang VARCHAR(255) DEFAULT NULL, resource_template_id INT NOT NULL, property_id INT NOT NULL, INDEX IDX_4689E2F116131EA (resource_template_id), INDEX IDX_4689E2F1549213EC (property_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE session (id VARCHAR(190) NOT NULL, data LONGBLOB NOT NULL, modified INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE setting (id VARCHAR(190) NOT NULL, value JSON NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(190) NOT NULL, theme VARCHAR(190) NOT NULL, title VARCHAR(190) NOT NULL, summary LONGTEXT DEFAULT NULL, navigation JSON NOT NULL, item_pool JSON NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, is_public TINYINT NOT NULL, assign_new_items TINYINT NOT NULL, thumbnail_id INT DEFAULT NULL, homepage_id INT DEFAULT NULL, owner_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_694309E4989D9B62 (slug), INDEX IDX_694309E4FDFF2E92 (thumbnail_id), UNIQUE INDEX UNIQ_694309E4571EDDA (homepage_id), INDEX IDX_694309E47E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site_block_attachment (id INT AUTO_INCREMENT NOT NULL, caption LONGTEXT NOT NULL, position INT NOT NULL, block_id INT NOT NULL, item_id INT DEFAULT NULL, media_id INT DEFAULT NULL, INDEX IDX_236473FEE9ED820C (block_id), INDEX IDX_236473FE126F525E (item_id), INDEX IDX_236473FEEA9FDD75 (media_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site_item_set (id INT AUTO_INCREMENT NOT NULL, position INT DEFAULT NULL, site_id INT NOT NULL, item_set_id INT NOT NULL, INDEX IDX_D4CE134F6BD1646 (site_id), INDEX IDX_D4CE134960278D7 (item_set_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site_page (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(190) NOT NULL, title VARCHAR(190) NOT NULL, is_public TINYINT NOT NULL, layout VARCHAR(190) DEFAULT NULL, layout_data JSON DEFAULT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, site_id INT NOT NULL, INDEX IDX_2F900BD9F6BD1646 (site_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site_page_block (id INT AUTO_INCREMENT NOT NULL, layout VARCHAR(80) NOT NULL, data JSON NOT NULL, layout_data JSON DEFAULT NULL, position INT NOT NULL, page_id INT NOT NULL, INDEX IDX_C593E731C4663E4 (page_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site_permission (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(80) NOT NULL, site_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_C0401D6FF6BD1646 (site_id), INDEX IDX_C0401D6FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE site_setting (id VARCHAR(190) NOT NULL, value JSON NOT NULL, site_id INT NOT NULL, INDEX IDX_64D05A53F6BD1646 (site_id), PRIMARY KEY (id, site_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(190) NOT NULL, name VARCHAR(190) NOT NULL, created DATETIME NOT NULL, modified DATETIME DEFAULT NULL, password_hash VARCHAR(60) DEFAULT NULL, role VARCHAR(190) NOT NULL, is_active TINYINT NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE user_setting (id VARCHAR(190) NOT NULL, value JSON NOT NULL, user_id INT NOT NULL, INDEX IDX_C779A692A76ED395 (user_id), PRIMARY KEY (id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE `value` (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(255) NOT NULL, lang VARCHAR(255) DEFAULT NULL, `value` LONGTEXT DEFAULT NULL, uri LONGTEXT DEFAULT NULL, is_public TINYINT NOT NULL, resource_id INT NOT NULL, property_id INT NOT NULL, value_resource_id INT DEFAULT NULL, value_annotation_id INT DEFAULT NULL, INDEX IDX_1D77583489329D25 (resource_id), INDEX IDX_1D775834549213EC (property_id), INDEX IDX_1D7758344BC72506 (value_resource_id), UNIQUE INDEX UNIQ_1D7758349B66727E (value_annotation_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE value_annotation (id INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE vocabulary (id INT AUTO_INCREMENT NOT NULL, namespace_uri VARCHAR(190) NOT NULL, prefix VARCHAR(190) NOT NULL, label VARCHAR(255) NOT NULL, comment LONGTEXT DEFAULT NULL, owner_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9099C97B9B267FDF (namespace_uri), UNIQUE INDEX UNIQ_9099C97B93B1868E (prefix), INDEX IDX_9099C97B7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE api_key ADD CONSTRAINT FK_C912ED9D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE asset ADD CONSTRAINT FK_2AF5A5C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE fulltext_search ADD CONSTRAINT FK_AA31FE4A7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251ECBE0B084 FOREIGN KEY (primary_media_id) REFERENCES media (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE item ADD CONSTRAINT FK_1F1B251EBF396750 FOREIGN KEY (id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_item_set ADD CONSTRAINT FK_6D0C9625126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_item_set ADD CONSTRAINT FK_6D0C9625960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_site ADD CONSTRAINT FK_A1734D1F126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_site ADD CONSTRAINT FK_A1734D1FF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE item_set ADD CONSTRAINT FK_1015EEEBF396750 FOREIGN KEY (id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE job ADD CONSTRAINT FK_FBD8E0F87E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10C126F525E FOREIGN KEY (item_id) REFERENCES item (id)');
        $this->addSql('ALTER TABLE media ADD CONSTRAINT FK_6A2CA10CBF396750 FOREIGN KEY (id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE password_creation ADD CONSTRAINT FK_C77917B4A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDE7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE property ADD CONSTRAINT FK_8BF21CDEAD0E05F6 FOREIGN KEY (vocabulary_id) REFERENCES vocabulary (id)');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F4167E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F41616131EA FOREIGN KEY (resource_template_id) REFERENCES resource_template (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource ADD CONSTRAINT FK_BC91F416FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES asset (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource_class ADD CONSTRAINT FK_C6F063AD7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource_class ADD CONSTRAINT FK_C6F063ADAD0E05F6 FOREIGN KEY (vocabulary_id) REFERENCES vocabulary (id)');
        $this->addSql('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E448CC1BD FOREIGN KEY (resource_class_id) REFERENCES resource_class (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52E724734A3 FOREIGN KEY (title_property_id) REFERENCES property (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource_template ADD CONSTRAINT FK_39ECD52EB84E0D1D FOREIGN KEY (description_property_id) REFERENCES property (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE resource_template_property ADD CONSTRAINT FK_4689E2F116131EA FOREIGN KEY (resource_template_id) REFERENCES resource_template (id)');
        $this->addSql('ALTER TABLE resource_template_property ADD CONSTRAINT FK_4689E2F1549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4FDFF2E92 FOREIGN KEY (thumbnail_id) REFERENCES asset (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E4571EDDA FOREIGN KEY (homepage_id) REFERENCES site_page (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE site ADD CONSTRAINT FK_694309E47E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE site_block_attachment ADD CONSTRAINT FK_236473FEE9ED820C FOREIGN KEY (block_id) REFERENCES site_page_block (id)');
        $this->addSql('ALTER TABLE site_block_attachment ADD CONSTRAINT FK_236473FE126F525E FOREIGN KEY (item_id) REFERENCES item (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE site_block_attachment ADD CONSTRAINT FK_236473FEEA9FDD75 FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE site_item_set ADD CONSTRAINT FK_D4CE134F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_item_set ADD CONSTRAINT FK_D4CE134960278D7 FOREIGN KEY (item_set_id) REFERENCES item_set (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_page ADD CONSTRAINT FK_2F900BD9F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id)');
        $this->addSql('ALTER TABLE site_page_block ADD CONSTRAINT FK_C593E731C4663E4 FOREIGN KEY (page_id) REFERENCES site_page (id)');
        $this->addSql('ALTER TABLE site_permission ADD CONSTRAINT FK_C0401D6FF6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_permission ADD CONSTRAINT FK_C0401D6FA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_setting ADD CONSTRAINT FK_64D05A53F6BD1646 FOREIGN KEY (site_id) REFERENCES site (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_setting ADD CONSTRAINT FK_C779A692A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `value` ADD CONSTRAINT FK_1D77583489329D25 FOREIGN KEY (resource_id) REFERENCES resource (id)');
        $this->addSql('ALTER TABLE `value` ADD CONSTRAINT FK_1D775834549213EC FOREIGN KEY (property_id) REFERENCES property (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `value` ADD CONSTRAINT FK_1D7758344BC72506 FOREIGN KEY (value_resource_id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `value` ADD CONSTRAINT FK_1D7758349B66727E FOREIGN KEY (value_annotation_id) REFERENCES value_annotation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE value_annotation ADD CONSTRAINT FK_C03BA4EBF396750 FOREIGN KEY (id) REFERENCES resource (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE vocabulary ADD CONSTRAINT FK_9099C97B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES user (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE api_key DROP FOREIGN KEY FK_C912ED9D7E3C61F9');
        $this->addSql('ALTER TABLE asset DROP FOREIGN KEY FK_2AF5A5C7E3C61F9');
        $this->addSql('ALTER TABLE fulltext_search DROP FOREIGN KEY FK_AA31FE4A7E3C61F9');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251ECBE0B084');
        $this->addSql('ALTER TABLE item DROP FOREIGN KEY FK_1F1B251EBF396750');
        $this->addSql('ALTER TABLE item_item_set DROP FOREIGN KEY FK_6D0C9625126F525E');
        $this->addSql('ALTER TABLE item_item_set DROP FOREIGN KEY FK_6D0C9625960278D7');
        $this->addSql('ALTER TABLE item_site DROP FOREIGN KEY FK_A1734D1F126F525E');
        $this->addSql('ALTER TABLE item_site DROP FOREIGN KEY FK_A1734D1FF6BD1646');
        $this->addSql('ALTER TABLE item_set DROP FOREIGN KEY FK_1015EEEBF396750');
        $this->addSql('ALTER TABLE job DROP FOREIGN KEY FK_FBD8E0F87E3C61F9');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10C126F525E');
        $this->addSql('ALTER TABLE media DROP FOREIGN KEY FK_6A2CA10CBF396750');
        $this->addSql('ALTER TABLE password_creation DROP FOREIGN KEY FK_C77917B4A76ED395');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDE7E3C61F9');
        $this->addSql('ALTER TABLE property DROP FOREIGN KEY FK_8BF21CDEAD0E05F6');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F4167E3C61F9');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416448CC1BD');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F41616131EA');
        $this->addSql('ALTER TABLE resource DROP FOREIGN KEY FK_BC91F416FDFF2E92');
        $this->addSql('ALTER TABLE resource_class DROP FOREIGN KEY FK_C6F063AD7E3C61F9');
        $this->addSql('ALTER TABLE resource_class DROP FOREIGN KEY FK_C6F063ADAD0E05F6');
        $this->addSql('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52E7E3C61F9');
        $this->addSql('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52E448CC1BD');
        $this->addSql('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52E724734A3');
        $this->addSql('ALTER TABLE resource_template DROP FOREIGN KEY FK_39ECD52EB84E0D1D');
        $this->addSql('ALTER TABLE resource_template_property DROP FOREIGN KEY FK_4689E2F116131EA');
        $this->addSql('ALTER TABLE resource_template_property DROP FOREIGN KEY FK_4689E2F1549213EC');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4FDFF2E92');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E4571EDDA');
        $this->addSql('ALTER TABLE site DROP FOREIGN KEY FK_694309E47E3C61F9');
        $this->addSql('ALTER TABLE site_block_attachment DROP FOREIGN KEY FK_236473FEE9ED820C');
        $this->addSql('ALTER TABLE site_block_attachment DROP FOREIGN KEY FK_236473FE126F525E');
        $this->addSql('ALTER TABLE site_block_attachment DROP FOREIGN KEY FK_236473FEEA9FDD75');
        $this->addSql('ALTER TABLE site_item_set DROP FOREIGN KEY FK_D4CE134F6BD1646');
        $this->addSql('ALTER TABLE site_item_set DROP FOREIGN KEY FK_D4CE134960278D7');
        $this->addSql('ALTER TABLE site_page DROP FOREIGN KEY FK_2F900BD9F6BD1646');
        $this->addSql('ALTER TABLE site_page_block DROP FOREIGN KEY FK_C593E731C4663E4');
        $this->addSql('ALTER TABLE site_permission DROP FOREIGN KEY FK_C0401D6FF6BD1646');
        $this->addSql('ALTER TABLE site_permission DROP FOREIGN KEY FK_C0401D6FA76ED395');
        $this->addSql('ALTER TABLE site_setting DROP FOREIGN KEY FK_64D05A53F6BD1646');
        $this->addSql('ALTER TABLE user_setting DROP FOREIGN KEY FK_C779A692A76ED395');
        $this->addSql('ALTER TABLE `value` DROP FOREIGN KEY FK_1D77583489329D25');
        $this->addSql('ALTER TABLE `value` DROP FOREIGN KEY FK_1D775834549213EC');
        $this->addSql('ALTER TABLE `value` DROP FOREIGN KEY FK_1D7758344BC72506');
        $this->addSql('ALTER TABLE `value` DROP FOREIGN KEY FK_1D7758349B66727E');
        $this->addSql('ALTER TABLE value_annotation DROP FOREIGN KEY FK_C03BA4EBF396750');
        $this->addSql('ALTER TABLE vocabulary DROP FOREIGN KEY FK_9099C97B7E3C61F9');
        $this->addSql('DROP TABLE api_key');
        $this->addSql('DROP TABLE asset');
        $this->addSql('DROP TABLE fulltext_search');
        $this->addSql('DROP TABLE item');
        $this->addSql('DROP TABLE item_item_set');
        $this->addSql('DROP TABLE item_site');
        $this->addSql('DROP TABLE item_set');
        $this->addSql('DROP TABLE job');
        $this->addSql('DROP TABLE media');
        $this->addSql('DROP TABLE migration');
        $this->addSql('DROP TABLE module');
        $this->addSql('DROP TABLE password_creation');
        $this->addSql('DROP TABLE property');
        $this->addSql('DROP TABLE resource');
        $this->addSql('DROP TABLE resource_class');
        $this->addSql('DROP TABLE resource_template');
        $this->addSql('DROP TABLE resource_template_property');
        $this->addSql('DROP TABLE session');
        $this->addSql('DROP TABLE setting');
        $this->addSql('DROP TABLE site');
        $this->addSql('DROP TABLE site_block_attachment');
        $this->addSql('DROP TABLE site_item_set');
        $this->addSql('DROP TABLE site_page');
        $this->addSql('DROP TABLE site_page_block');
        $this->addSql('DROP TABLE site_permission');
        $this->addSql('DROP TABLE site_setting');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_setting');
        $this->addSql('DROP TABLE `value`');
        $this->addSql('DROP TABLE value_annotation');
        $this->addSql('DROP TABLE vocabulary');
    }
}
