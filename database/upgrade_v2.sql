-- Run this once if you already imported the first schema.sql. It only adds the payments table.
USE diorwa_realestate;
CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  viewing_id INT NOT NULL,
  property_id INT NOT NULL,
  user_id INT NULL,
  provider ENUM('mtn','airtel') NOT NULL,
  phone VARCHAR(15) NOT NULL,            -- 2507XXXXXXXX
  amount_rwf INT NOT NULL,
  reference CHAR(36) NOT NULL UNIQUE,    -- sent to the provider as the request id
  status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_viewing (viewing_id),
  FOREIGN KEY (viewing_id) REFERENCES viewings(id) ON DELETE CASCADE
) ENGINE=InnoDB;
