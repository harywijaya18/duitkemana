-- =====================================================
-- Income Tracking Migration for DuitKemana
-- Run via: mysql -u root duitkemana < migrate_income.sql
-- =====================================================

USE duitkemana;

-- Salary configuration per user
CREATE TABLE IF NOT EXISTS salary_configs (
    id                          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id                     INT UNSIGNED NOT NULL,
    name                        VARCHAR(100) NOT NULL DEFAULT 'Gaji Utama',
    base_salary                 DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    meal_allowance_per_day      DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tunjangan makan per hari kerja',
    transport_allowance_per_day DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT 'Tunjangan transportasi per hari kerja',
    position_allowance          DECIMAL(15,2) NOT NULL DEFAULT 0.00 COMMENT 'Tunjangan jabatan (nominal tetap/bulan)',
    cutoff_day                  TINYINT UNSIGNED NOT NULL DEFAULT 0  COMMENT '0=akhir bulan, 1-28=tanggal tertentu',
    working_days_per_week       TINYINT UNSIGNED NOT NULL DEFAULT 5  COMMENT '5=Senin-Jumat, 6=Senin-Sabtu',
    is_active                   TINYINT(1) NOT NULL DEFAULT 0,
    created_at                  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Monthly income records
CREATE TABLE IF NOT EXISTS income_records (
    id                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id              INT UNSIGNED NOT NULL,
    salary_config_id     INT UNSIGNED DEFAULT NULL,
    source_name          VARCHAR(100) NOT NULL,
    period_year          SMALLINT UNSIGNED NOT NULL,
    period_month         TINYINT UNSIGNED NOT NULL,
    base_salary          DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    meal_allowance       DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    transport_allowance  DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    position_allowance   DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    other_income         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    working_days         INT NOT NULL DEFAULT 0,
    total_income         DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    received_date        DATE DEFAULT NULL,
    notes                TEXT DEFAULT NULL,
    created_at           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (salary_config_id) REFERENCES salary_configs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
