-- Ejecutar solo si ya tienes la BD creada sin email ni mfa_codes:
-- mariadb -u root -p kanan_web < database_migration_mfa.sql

USE kanan_web;

ALTER TABLE users
  ADD COLUMN email VARCHAR(255) NULL AFTER nombre,
  ADD UNIQUE KEY uk_users_email (email);

CREATE TABLE IF NOT EXISTS mfa_codes (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code CHAR(6) NOT NULL,
  expires_at DATETIME NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_mfa_user_expires (user_id, expires_at),
  CONSTRAINT fk_mfa_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
