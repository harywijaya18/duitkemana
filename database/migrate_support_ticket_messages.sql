USE duitkemana;

ALTER TABLE support_tickets
    ADD COLUMN IF NOT EXISTS initial_message MEDIUMTEXT NULL AFTER subject;

UPDATE support_tickets
SET initial_message = subject
WHERE (initial_message IS NULL OR initial_message = '');