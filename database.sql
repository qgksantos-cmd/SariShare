-- SariShare File Portal database
-- Import with phpMyAdmin or: mysql -u root -p < database.sql

CREATE DATABASE IF NOT EXISTS sarishare
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE sarishare;

CREATE TABLE IF NOT EXISTS companies (
  company_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  company_name VARCHAR(150) NOT NULL,
  PRIMARY KEY (company_id),
  UNIQUE KEY uq_companies_name (company_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS roles (
  role_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  role_name VARCHAR(80) NOT NULL,
  PRIMARY KEY (role_id),
  UNIQUE KEY uq_roles_name (role_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS branches (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(120) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_branches_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS users (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  branch_id INT UNSIGNED NOT NULL,
  role_id INT UNSIGNED NOT NULL DEFAULT 1,
  company_id INT UNSIGNED NOT NULL DEFAULT 1,
  full_name VARCHAR(150) NOT NULL,
  email VARCHAR(190) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_branch (branch_id),
  KEY idx_users_role (role_id),
  KEY idx_users_company (company_id),
  CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (role_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_users_company FOREIGN KEY (company_id) REFERENCES companies (company_id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_users_branch FOREIGN KEY (branch_id) REFERENCES branches (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  name VARCHAR(80) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS files (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  category_id INT UNSIGNED NOT NULL,
  uploaded_by INT UNSIGNED NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  storage_path VARCHAR(500) NOT NULL,
  file_size_bytes BIGINT UNSIGNED NOT NULL DEFAULT 0,
  mime_type VARCHAR(100) NOT NULL DEFAULT 'application/pdf',
  visibility ENUM('branch', 'all_staff', 'managers') NOT NULL DEFAULT 'branch',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_files_category (category_id),
  KEY idx_files_uploader (uploaded_by),
  KEY idx_files_updated (updated_at),
  CONSTRAINT fk_files_category FOREIGN KEY (category_id) REFERENCES categories (id)
    ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_files_uploader FOREIGN KEY (uploaded_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS access_requests (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  file_id INT UNSIGNED NOT NULL,
  requester_id INT UNSIGNED NOT NULL,
  status ENUM('pending', 'approved', 'denied', 'cancelled') NOT NULL DEFAULT 'pending',
  reviewed_by INT UNSIGNED NULL,
  review_note VARCHAR(500) NULL,
  requested_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  reviewed_at TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_active_file_request (file_id, requester_id, status),
  KEY idx_requests_requester (requester_id),
  KEY idx_requests_reviewer (reviewed_by),
  CONSTRAINT fk_requests_file FOREIGN KEY (file_id) REFERENCES files (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_requests_requester FOREIGN KEY (requester_id) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE CASCADE,
  CONSTRAINT fk_requests_reviewer FOREIGN KEY (reviewed_by) REFERENCES users (id)
    ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO companies (company_id, company_name) VALUES
  (1, 'SariShare')
ON DUPLICATE KEY UPDATE company_name = VALUES(company_name);

INSERT INTO roles (role_id, role_name) VALUES
  (1, 'staff'),
  (2, 'manager'),
  (3, 'admin')
ON DUPLICATE KEY UPDATE role_name = VALUES(role_name);

INSERT INTO branches (name) VALUES
  ('Sta. Rosa Branch')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO categories (name) VALUES
  ('Sales')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (branch_id, role_id, full_name, email, password_hash)
SELECT id, 1, 'Maria Santos', 'maria.santos@example.com',
  '$2y$10$Ne3968iDgQHO4oec88pyAOBHkEBfvunpr2OZuoZgrJTPxEIEQUfEy'
FROM branches
WHERE name = 'Sta. Rosa Branch'
  AND NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'maria.santos@example.com'
  );

INSERT INTO users (branch_id, role_id, full_name, email, password_hash)
SELECT id, 2, 'Daniel Reyes', 'daniel.reyes@example.com',
       '$2y$10$Ne3968iDgQHO4oec88pyAOBHkEBfvunpr2OZuoZgrJTPxEIEQUfEy'
FROM branches
WHERE name = 'Sta. Rosa Branch'
  AND NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'daniel.reyes@example.com'
  );

INSERT INTO users (branch_id, role_id, full_name, email, password_hash)
SELECT id, 1, 'Jose Lim', 'jose.lim@example.com',
       '$2y$10$Ne3968iDgQHO4oec88pyAOBHkEBfvunpr2OZuoZgrJTPxEIEQUfEy'
FROM branches
WHERE name = 'Sta. Rosa Branch'
  AND NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'jose.lim@example.com'
  );

INSERT INTO users (branch_id, role_id, full_name, email, password_hash)
SELECT id, 1, 'Ana Cruz', 'ana.cruz@example.com',
       '$2y$10$Ne3968iDgQHO4oec88pyAOBHkEBfvunpr2OZuoZgrJTPxEIEQUfEy'
FROM branches
WHERE name = 'Sta. Rosa Branch'
  AND NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'ana.cruz@example.com'
  );

INSERT INTO files (category_id, uploaded_by, file_name, storage_path, file_size_bytes, mime_type, visibility, created_at)
SELECT categories.id, users.id, 'Daily Sales Report - September 2026.pdf',
       'uploads/daily-sales-report-september-2026.pdf', 862208, 'application/pdf', 'all_staff',
       '2026-09-03 09:00:00'
FROM categories
JOIN users ON users.email = 'maria.santos@example.com'
WHERE categories.name = 'Sales'
  AND NOT EXISTS (
    SELECT 1 FROM files WHERE file_name = 'Daily Sales Report - September 2026.pdf'
  );

INSERT INTO access_requests (file_id, requester_id, status, review_note, requested_at)
SELECT files.id, users.id, 'pending',
       'Need to verify current wholesale prices before processing a bulk order from a customer.',
       '2026-09-03 09:14:00'
FROM files
JOIN users ON users.email = 'maria.santos@example.com'
WHERE files.file_name = 'Daily Sales Report - September 2026.pdf'
  AND NOT EXISTS (
    SELECT 1 FROM access_requests existing
    WHERE existing.file_id = files.id AND existing.requester_id = users.id
      AND existing.status = 'pending'
  );

-- The portal can use this query to populate the All Files view.
-- SELECT f.id, f.file_name, c.name AS category, f.file_size_bytes,
--        f.updated_at, ar.status AS request_status
-- FROM files f
-- JOIN categories c ON c.id = f.category_id
-- LEFT JOIN access_requests ar ON ar.file_id = f.id
--   AND ar.requester_id = 1
--   AND ar.status IN ('pending', 'approved')
-- ORDER BY f.updated_at DESC;
