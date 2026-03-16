-- =============================================================
-- DuitKemana - Demo Seed Data
-- Run: import file ini via phpMyAdmin ke database: budget
-- =============================================================

USE duitkemana;

-- Demo User
INSERT INTO users (id, name, email, password, currency, created_at) VALUES
(1, 'Demo User', 'demo@duitkemana.com', '$2y$10$vCYC.Wf7AV4VqYj/C67on./.rACF8EoCfDmOYNbrR8gHEOb84o.f2', 'IDR', NOW())
ON DUPLICATE KEY UPDATE id = id;

-- Default Categories for Demo User
INSERT INTO categories (user_id, name, icon) VALUES
(1, 'Food',          'fa-utensils'),
(1, 'Transport',     'fa-motorcycle'),
(1, 'Shopping',      'fa-bag-shopping'),
(1, 'Bills',         'fa-file-invoice-dollar'),
(1, 'Entertainment', 'fa-film'),
(1, 'Other',         'fa-wallet')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Monthly Budget for current month
INSERT INTO budgets (user_id, month, year, amount) VALUES
(1, 3, 2026, 5000000)
ON DUPLICATE KEY UPDATE amount = VALUES(amount);

-- Sample Transactions
INSERT INTO transactions (user_id, category_id, amount, payment_method_id, description, transaction_date) VALUES
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Food' LIMIT 1),        35000,  1, 'Makan siang warung',   '2026-03-15'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Transport' LIMIT 1),   25000,  5, 'Ojek online',          '2026-03-15'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Shopping' LIMIT 1),   150000,  3, 'Beli baju',            '2026-03-14'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Bills' LIMIT 1),      200000,  2, 'Bayar listrik',        '2026-03-13'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Entertainment' LIMIT 1),50000, 5, 'Nonton bioskop',       '2026-03-12'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Food' LIMIT 1),        20000,  1, 'Sarapan nasi uduk',    '2026-03-12'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Transport' LIMIT 1),   15000,  5, 'Grab ke kantor',       '2026-03-11'),
(1, (SELECT id FROM categories WHERE user_id=1 AND name='Food' LIMIT 1),        45000,  4, 'Makan malam resto',    '2026-03-10');
