-- Run this ONCE in phpMyAdmin (select the cityfix database, open the SQL tab, paste, Go).
-- It is safe to run again: it only adds columns that are missing.
ALTER TABLE users ADD COLUMN IF NOT EXISTS points INT NOT NULL DEFAULT 0;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS verified TINYINT(1) NOT NULL DEFAULT 0;
ALTER TABLE complaints ADD COLUMN IF NOT EXISTS auto_category TINYINT(1) NOT NULL DEFAULT 0;
