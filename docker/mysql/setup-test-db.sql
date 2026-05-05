-- Idempotent setup for the test database.
-- The MYSQL_USER (default `app`) is provisioned by the database container with
-- privileges on MYSQL_DATABASE (default `akhilleus`) only — Symfony Flex's
-- `dbname_suffix: '_test%env(default::TEST_TOKEN)%'` then creates `akhilleus_test`
-- (and `akhilleus_test1`, `_test2`, … for parallel suites), which the app user
-- has no privileges on by default. This script grants them.
--
-- Run via `composer setup:test-db` whenever the test DB is missing or the user
-- can't access it (typical on a fresh machine, after `docker compose down -v`,
-- or after a manual MySQL volume reset).

CREATE DATABASE IF NOT EXISTS `akhilleus_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `akhilleus\_test%`.* TO 'app'@'%';
FLUSH PRIVILEGES;
