-- ================================================================
-- Salary Deductions migration
-- ================================================================

CREATE TABLE IF NOT EXISTS salary_deductions (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    salary_config_id INT UNSIGNED NOT NULL,
    name             VARCHAR(100) NOT NULL,
    type             ENUM('percentage', 'fixed') NOT NULL DEFAULT 'percentage',
    rate             DECIMAL(8,4)  NULL    COMMENT 'Percentage rate, e.g. 2.0000 = 2%',
    base_type        ENUM('basic_only','basic_fixed') NOT NULL DEFAULT 'basic_fixed'
                     COMMENT 'basic_only=Gaji Pokok only, basic_fixed=Gaji Pokok+Tunjangan Jabatan',
    base_cap         DECIMAL(15,2) NULL    COMMENT 'Max base amount for percentage calc (NULL=no cap)',
    fixed_amount     DECIMAL(15,2) NULL    COMMENT 'Absolute amount for fixed type',
    sort_order       TINYINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (id),
    FOREIGN KEY (salary_config_id) REFERENCES salary_configs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add total_deductions to income_records (net_income = total_income - total_deductions)
ALTER TABLE income_records
    ADD COLUMN total_deductions DECIMAL(15,2) NOT NULL DEFAULT 0.00 AFTER total_income;
