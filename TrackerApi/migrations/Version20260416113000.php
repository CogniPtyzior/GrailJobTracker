<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260416113000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add performance indexes for tracked_jobs text search and owner/date pagination.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS pg_trgm');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tracked_jobs_owner_updated_at ON trackers.tracked_jobs (owner_id, updated_at DESC)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tracked_jobs_company_trgm ON trackers.tracked_jobs USING GIN (LOWER(company) gin_trgm_ops)');
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_tracked_jobs_title_trgm ON trackers.tracked_jobs USING GIN (LOWER(title) gin_trgm_ops)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS trackers.idx_tracked_jobs_title_trgm');
        $this->addSql('DROP INDEX IF EXISTS trackers.idx_tracked_jobs_company_trgm');
        $this->addSql('DROP INDEX IF EXISTS trackers.idx_tracked_jobs_owner_updated_at');
    }
}
