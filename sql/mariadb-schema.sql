-- Schema base para o SaaS de encurtador de links usando Shlink como motor.
-- Objetivo:
-- - controlar usuários, planos, assinaturas e limites mensais;
-- - registrar domínios próprios dos clientes;
-- - persistir o espelho operacional dos links criados via API do Shlink;
-- - manter auditoria suficiente para relatórios e suporte.

SET NAMES utf8mb4;
SET time_zone = '+00:00';

CREATE TABLE IF NOT EXISTS plans (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  code VARCHAR(32) NOT NULL,
  name VARCHAR(80) NOT NULL,
  description VARCHAR(255) NULL,
  is_free TINYINT(1) NOT NULL DEFAULT 0,
  monthly_short_url_limit INT UNSIGNED NULL,
  allow_custom_slug TINYINT(1) NOT NULL DEFAULT 0,
  allow_custom_domain TINYINT(1) NOT NULL DEFAULT 0,
  allow_custom_expiration TINYINT(1) NOT NULL DEFAULT 0,
  allow_lifetime_links TINYINT(1) NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_plans_code (code),
  KEY idx_plans_active (is_active, is_free)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  plan_id BIGINT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('owner', 'customer', 'visitor') NOT NULL DEFAULT 'customer',
  status ENUM('pending', 'active', 'suspended', 'deleted') NOT NULL DEFAULT 'pending',
  timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
  locale VARCHAR(16) NOT NULL DEFAULT 'pt-BR',
  email_verified_at TIMESTAMP NULL DEFAULT NULL,
  last_login_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_plan_status (plan_id, status),
  CONSTRAINT fk_users_plan
    FOREIGN KEY (plan_id) REFERENCES plans (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  provider VARCHAR(40) NOT NULL DEFAULT 'manual',
  provider_customer_id VARCHAR(120) NULL,
  provider_subscription_id VARCHAR(120) NULL,
  status ENUM('trialing', 'active', 'past_due', 'canceled', 'expired') NOT NULL DEFAULT 'trialing',
  current_period_start DATETIME NULL,
  current_period_end DATETIME NULL,
  cancel_at_period_end TINYINT(1) NOT NULL DEFAULT 0,
  metadata JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_subscriptions_provider (provider, provider_subscription_id),
  KEY idx_subscriptions_user_status (user_id, status),
  KEY idx_subscriptions_period (current_period_start, current_period_end),
  CONSTRAINT fk_subscriptions_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_subscriptions_plan
    FOREIGN KEY (plan_id) REFERENCES plans (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS customer_domains (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  domain VARCHAR(190) NOT NULL,
  status ENUM('pending_dns', 'pending_ssl', 'active', 'suspended', 'disabled') NOT NULL DEFAULT 'pending_dns',
  is_primary TINYINT(1) NOT NULL DEFAULT 0,
  dns_target VARCHAR(190) NULL,
  dns_verified_at TIMESTAMP NULL DEFAULT NULL,
  shlink_domain_registered_at TIMESTAMP NULL DEFAULT NULL,
  tls_mode ENUM('on_demand', 'managed', 'external') NOT NULL DEFAULT 'on_demand',
  tls_status ENUM('unknown', 'pending', 'active', 'failed') NOT NULL DEFAULT 'unknown',
  tls_last_error VARCHAR(255) NULL,
  shlink_domain_payload JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_customer_domains_domain (domain),
  KEY idx_customer_domains_user_status (user_id, status),
  KEY idx_customer_domains_primary (user_id, is_primary),
  CONSTRAINT fk_customer_domains_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS short_links (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  customer_domain_id BIGINT UNSIGNED NULL,
  plan_id BIGINT UNSIGNED NOT NULL,
  shlink_short_url VARCHAR(500) NULL,
  shlink_short_code VARCHAR(191) NULL,
  domain VARCHAR(190) NOT NULL,
  long_url TEXT NOT NULL,
  custom_slug VARCHAR(190) NULL,
  generated_slug VARCHAR(190) NULL,
  is_custom_slug TINYINT(1) NOT NULL DEFAULT 0,
  is_free_link TINYINT(1) NOT NULL DEFAULT 0,
  valid_until DATETIME NULL,
  valid_since DATETIME NULL,
  status ENUM('queued', 'active', 'expired', 'disabled', 'error') NOT NULL DEFAULT 'queued',
  created_via ENUM('panel', 'api', 'import', 'system') NOT NULL DEFAULT 'panel',
  shlink_payload JSON NULL,
  shlink_response JSON NULL,
  last_stats_sync_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_short_links_short_code (shlink_short_code),
  UNIQUE KEY uq_short_links_short_url (shlink_short_url),
  KEY idx_short_links_user_created (user_id, created_at),
  KEY idx_short_links_user_free_month (user_id, is_free_link, created_at),
  KEY idx_short_links_customer_domain (customer_domain_id, status),
  KEY idx_short_links_domain_status (domain, status),
  CONSTRAINT fk_short_links_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_short_links_plan
    FOREIGN KEY (plan_id) REFERENCES plans (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_short_links_domain
    FOREIGN KEY (customer_domain_id) REFERENCES customer_domains (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS monthly_quota_usage (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id BIGINT UNSIGNED NOT NULL,
  quota_month CHAR(7) NOT NULL,
  free_links_created INT UNSIGNED NOT NULL DEFAULT 0,
  free_links_rejected INT UNSIGNED NOT NULL DEFAULT 0,
  last_free_link_at DATETIME NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_monthly_quota_user_month (user_id, quota_month),
  KEY idx_monthly_quota_month (quota_month),
  CONSTRAINT fk_monthly_quota_user
    FOREIGN KEY (user_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS link_event_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  short_link_id BIGINT UNSIGNED NOT NULL,
  event_type ENUM('create', 'update', 'expire', 'disable', 'sync_domain', 'sync_stats', 'api_error') NOT NULL,
  severity ENUM('info', 'warning', 'error') NOT NULL DEFAULT 'info',
  message VARCHAR(255) NOT NULL,
  payload JSON NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_link_event_short_link (short_link_id, created_at),
  KEY idx_link_event_type (event_type, severity),
  CONSTRAINT fk_link_event_short_link
    FOREIGN KEY (short_link_id) REFERENCES short_links (id)
    ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE VIEW v_free_link_usage_current_month AS
SELECT
  u.id AS user_id,
  u.email,
  p.code AS plan_code,
  p.monthly_short_url_limit,
  COALESCE(mq.free_links_created, 0) AS free_links_created,
  COALESCE(mq.free_links_rejected, 0) AS free_links_rejected,
  mq.quota_month
FROM users u
JOIN plans p ON p.id = u.plan_id
LEFT JOIN monthly_quota_usage mq
  ON mq.user_id = u.id
 AND mq.quota_month = DATE_FORMAT(UTC_DATE(), '%Y-%m');

-- Seeds sugeridos:
-- INSERT INTO plans (code, name, description, is_free, monthly_short_url_limit, allow_custom_slug, allow_custom_domain, allow_custom_expiration, allow_lifetime_links)
-- VALUES
-- ('free', 'Free', 'Até 5 links por mês, validade fixa de 7 dias.', 1, 5, 0, 0, 0, 0),
-- ('premium', 'Premium', 'Links ilimitados, slug customizado, domínio próprio e expiração flexível.', 0, NULL, 1, 1, 1, 1),
-- ('owner', 'Owner', 'Plano interno com privilégios totais.', 0, NULL, 1, 1, 1, 1);
