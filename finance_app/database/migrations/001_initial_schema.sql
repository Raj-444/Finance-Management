-- Migration: 001_initial_schema.sql
-- Initial schema setup for Personal Finance Manager

-- App metadata tracking
CREATE TABLE IF NOT EXISTS schema_migrations (
  version VARCHAR(100) PRIMARY KEY,
  applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);
