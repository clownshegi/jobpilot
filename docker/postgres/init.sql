-- Runs once, on the first start of an empty data volume.
-- pgvector in the main database, plus a separate database for the test suite.
CREATE EXTENSION IF NOT EXISTS vector;

CREATE DATABASE jobpilot_testing;
\connect jobpilot_testing
CREATE EXTENSION IF NOT EXISTS vector;
