-- Migration: Budget Goals by Category
-- Run after schema.sql

CREATE TABLE IF NOT EXISTS budget_goals (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id   INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    month     TINYINT UNSIGNED NOT NULL,
    year      SMALLINT UNSIGNED NOT NULL,
    goal_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_budget_goals_user     FOREIGN KEY (user_id)     REFERENCES users(id)      ON DELETE CASCADE,
    CONSTRAINT fk_budget_goals_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_goal_user_cat_period (user_id, category_id, month, year),
    INDEX idx_budget_goals_user_period (user_id, month, year)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
