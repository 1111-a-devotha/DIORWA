-- Run once if you already have the payments table from upgrade_v2.sql or an older schema.sql.
-- In phpMyAdmin, click the diorwa_realestate database first, then use the Import tab.
USE diorwa_realestate;
ALTER TABLE payments
  MODIFY provider ENUM('mtn','airtel','bank') NOT NULL,
  MODIFY phone VARCHAR(15) NULL,
  ADD COLUMN bank_ref VARCHAR(60) NULL AFTER phone;
