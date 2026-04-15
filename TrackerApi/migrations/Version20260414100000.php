<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260414100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tracker schema and initial tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA IF NOT EXISTS trackers');

        $this->addSql('CREATE TABLE trackers.users (
            id UUID NOT NULL,
            email VARCHAR(180) NOT NULL,
            normalized_email VARCHAR(180) NOT NULL,
            first_name VARCHAR(120) DEFAULT NULL,
            last_name VARCHAR(120) DEFAULT NULL,
            is_active BOOLEAN NOT NULL,
            roles JSON NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            last_login_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE UNIQUE INDEX uniq_users_normalized_email ON trackers.users (normalized_email)');
        $this->addSql('CREATE INDEX idx_users_normalized_email ON trackers.users (normalized_email)');
        $this->addSql('CREATE INDEX idx_users_is_active ON trackers.users (is_active)');

        $this->addSql('CREATE TABLE trackers.access_requests (
            id UUID NOT NULL,
            email VARCHAR(180) NOT NULL,
            normalized_email VARCHAR(180) NOT NULL,
            company_name VARCHAR(255) NOT NULL,
            reason TEXT NOT NULL,
            first_name VARCHAR(120) DEFAULT NULL,
            last_name VARCHAR(120) DEFAULT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_access_requests_normalized_email ON trackers.access_requests (normalized_email)');
        $this->addSql('CREATE INDEX idx_access_requests_company_name ON trackers.access_requests (company_name)');
        $this->addSql('CREATE INDEX idx_access_requests_created_at ON trackers.access_requests (created_at)');

        $this->addSql('CREATE TABLE trackers.tracked_jobs (
            id UUID NOT NULL,
            owner_id UUID NOT NULL,
            company VARCHAR(255) DEFAULT NULL,
            title VARCHAR(255) DEFAULT NULL,
            contract_type VARCHAR(255) DEFAULT NULL,
            location VARCHAR(255) DEFAULT NULL,
            remote_mode VARCHAR(255) DEFAULT NULL,
            remuneration VARCHAR(255) DEFAULT NULL,
            offer_url TEXT DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            application_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            planned_follow_up_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            effective_follow_up_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            first_contact_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            preliminary_interview_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            second_interview_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL,
            hr_contact_name VARCHAR(255) DEFAULT NULL,
            business_contact_name VARCHAR(255) DEFAULT NULL,
            subjective_relevance INT DEFAULT NULL,
            status VARCHAR(255) NOT NULL,
            created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX idx_tracked_jobs_status ON trackers.tracked_jobs (status)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_contract_type ON trackers.tracked_jobs (contract_type)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_remote_mode ON trackers.tracked_jobs (remote_mode)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_application_date ON trackers.tracked_jobs (application_date)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_followup_date ON trackers.tracked_jobs (planned_follow_up_date)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_relevance ON trackers.tracked_jobs (subjective_relevance)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_owner ON trackers.tracked_jobs (owner_id)');
        $this->addSql('CREATE INDEX idx_tracked_jobs_company_lower ON trackers.tracked_jobs (LOWER(company))');
        $this->addSql('ALTER TABLE trackers.tracked_jobs ADD CONSTRAINT fk_tracked_jobs_owner FOREIGN KEY (owner_id) REFERENCES trackers.users (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE trackers.tracked_jobs');
        $this->addSql('DROP TABLE trackers.access_requests');
        $this->addSql('DROP TABLE trackers.users');
    }
}
