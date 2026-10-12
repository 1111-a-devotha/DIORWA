-- DIORWA RealEstate database
-- Import with phpMyAdmin (Import tab) or:  mysql -u root < database/schema.sql
CREATE DATABASE IF NOT EXISTS diorwa_realestate CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diorwa_realestate;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  phone VARCHAR(30) NOT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','agent','admin') NOT NULL DEFAULT 'user',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS properties (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT,
  type ENUM('house','apartment','land','commercial') NOT NULL,
  listing_type ENUM('sale','rent') NOT NULL,
  price DECIMAL(12,2) NOT NULL,          -- USD. For rentals this is per month.
  city VARCHAR(80) NOT NULL,
  address VARCHAR(200) NULL,
  bedrooms TINYINT NULL,
  bathrooms TINYINT NULL,
  area_m2 INT NOT NULL,
  image VARCHAR(200) NOT NULL,           -- main image (images/ or uploads/)
  amenities VARCHAR(300) NULL,           -- comma separated
  lat DECIMAL(9,6) NULL,
  lng DECIMAL(9,6) NULL,
  upi VARCHAR(30) NULL,                  -- Rwanda Unique Parcel Identifier (optional)
  title_verified TINYINT(1) NOT NULL DEFAULT 0,  -- set by admin only
  status ENUM('pending','approved','rejected','sold') NOT NULL DEFAULT 'pending',
  featured TINYINT(1) NOT NULL DEFAULT 0,
  views INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_status (status), INDEX idx_city (city), INDEX idx_type (type, listing_type),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favorites (
  user_id INT NOT NULL,
  property_id INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, property_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS inquiries (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  user_id INT NULL,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  phone VARCHAR(30) NULL,
  message TEXT NOT NULL,
  status ENUM('new','replied') NOT NULL DEFAULT 'new',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS viewings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  property_id INT NOT NULL,
  user_id INT NULL,
  name VARCHAR(120) NOT NULL,
  phone VARCHAR(30) NOT NULL,
  preferred_date DATE NOT NULL,
  preferred_time VARCHAR(20) NOT NULL,
  status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  viewing_id INT NOT NULL,
  property_id INT NOT NULL,
  user_id INT NULL,
  provider ENUM('mtn','airtel','bank') NOT NULL,
  phone VARCHAR(15) NULL,                -- 2507XXXXXXXX (mobile money)
  bank_ref VARCHAR(60) NULL,             -- customer's bank transfer reference
  amount_rwf INT NOT NULL,
  reference CHAR(36) NOT NULL UNIQUE,    -- sent to the provider as the request id
  status ENUM('pending','paid','failed') NOT NULL DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_viewing (viewing_id),
  FOREIGN KEY (viewing_id) REFERENCES viewings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS contacts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  subject VARCHAR(200) NOT NULL,
  message TEXT NOT NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Default admin account. CHANGE THE PASSWORD after first login (Dashboard > Change password).
INSERT INTO users (fullname,email,phone,password,role) VALUES
('DIORWA Admin','admin@diorwa.rw','+250000000000','$2y$10$NtxNfPdcUKbqp6wCFxzUvepiy8qiyGzgTQz8f8trQUMgxIQaOtcLm','admin')
ON DUPLICATE KEY UPDATE email=email;

-- Sample listings (coordinates are approximate city centres; no title verification is claimed)
INSERT INTO properties (title,description,type,listing_type,price,city,bedrooms,bathrooms,area_m2,image,amenities,lat,lng,status,featured) VALUES
('Luxury Villa','A well-built home in Kigali offering comfortable family living space on a secure plot.','house','sale',320000,'Kigali',5,4,450,'h1.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.9441,30.0619,'approved',1),
('Executive Apartment','A bright apartment in Musanze with modern finishes, reliable utilities and easy access to the city.','apartment','rent',950,'Musanze',3,2,180,'h3.jpg','Elevator,Parking,Backup power,Security,Internet-ready',-1.4995,29.635,'approved',1),
('Residential Plot','Land in Huye, suitable for development or investment, with accessible roads and nearby utilities.','land','sale',45000,'Huye',NULL,NULL,700,'p3.jpg','Road access,Electricity nearby,Water nearby,Clear boundaries',-2.5967,29.7394,'approved',1),
('Office Building','A commercial property in Kigali with good visibility and access, suited to business use.','commercial','sale',510000,'Kigali',NULL,NULL,900,'h4.jpg','Parking,Loading area,Three-phase power,Security,Road frontage',-1.9441,30.0619,'approved',1),
('Modern Family House','A well-built home in Rubavu offering comfortable family living space on a secure plot.','house','rent',1100,'Rubavu',4,3,300,'h5.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.6792,29.2597,'approved',1),
('Penthouse Apartment','A bright apartment in Kigali with modern finishes, reliable utilities and easy access to the city.','apartment','sale',280000,'Kigali',4,3,260,'h6.jpg','Elevator,Parking,Backup power,Security,Internet-ready',-1.9441,30.0619,'approved',1),
('Farm Land','Land in Nyagatare, suitable for development or investment, with accessible roads and nearby utilities.','land','sale',120000,'Nyagatare',NULL,NULL,50000,'p7.jpg','Road access,Electricity nearby,Water nearby,Clear boundaries',-1.297,30.328,'approved',0),
('Warehouse','A commercial property in Kigali with good visibility and access, suited to business use.','commercial','rent',2800,'Kigali',NULL,NULL,1500,'h8.jpg','Parking,Loading area,Three-phase power,Security,Road frontage',-1.9441,30.0619,'approved',0),
('Smart Home','A well-built home in Huye offering comfortable family living space on a secure plot.','house','sale',260000,'Huye',4,3,380,'h9.jpg','Parking,Garden,Water tank,Security,Fenced compound',-2.5967,29.7394,'approved',0),
('Commercial Plot','Land in Musanze, suitable for development or investment, with accessible roads and nearby utilities.','land','sale',78000,'Musanze',NULL,NULL,1200,'p1.jpg','Road access,Electricity nearby,Water nearby,Clear boundaries',-1.4995,29.635,'approved',0),
('Studio Apartment','A bright apartment in Kigali with modern finishes, reliable utilities and easy access to the city.','apartment','rent',550,'Kigali',1,1,80,'h11.jpg','Elevator,Parking,Backup power,Security,Internet-ready',-1.9441,30.0619,'approved',0),
('Country House','A well-built home in Gicumbi offering comfortable family living space on a secure plot.','house','sale',145000,'Gicumbi',3,2,240,'h11.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.578,30.068,'approved',0),
('Lake View Plot','Land in Rubavu, suitable for development or investment, with accessible roads and nearby utilities.','land','sale',68000,'Rubavu',NULL,NULL,850,'p5.jpg','Road access,Electricity nearby,Water nearby,Clear boundaries',-1.6792,29.2597,'approved',0),
('Shopping Space','A commercial property in Kigali with good visibility and access, suited to business use.','commercial','rent',1800,'Kigali',NULL,NULL,350,'h4.jpg','Parking,Loading area,Three-phase power,Security,Road frontage',-1.9441,30.0619,'approved',0),
('Duplex House','A well-built home in Rusizi offering comfortable family living space on a secure plot.','house','sale',215000,'Rusizi',4,3,340,'h3.jpg','Parking,Garden,Water tank,Security,Fenced compound',-2.4846,28.907,'approved',0),
('Luxury Penthouse','A bright apartment in Kigali with modern finishes, reliable utilities and easy access to the city.','apartment','rent',2200,'Kigali',4,4,320,'h9.jpg','Elevator,Parking,Backup power,Security,Internet-ready',-1.9441,30.0619,'approved',0),
('Industrial Plot','Land in Kigali, suitable for development or investment, with accessible roads and nearby utilities.','land','sale',180000,'Kigali',NULL,NULL,2000,'p8.jpg','Road access,Electricity nearby,Water nearby,Clear boundaries',-1.9441,30.0619,'approved',0),
('Business Center','A commercial property in Huye with good visibility and access, suited to business use.','commercial','sale',460000,'Huye',NULL,NULL,1200,'h10.jpg','Parking,Loading area,Three-phase power,Security,Road frontage',-2.5967,29.7394,'approved',0),
('Holiday Cottage','A well-built home in Karongi offering comfortable family living space on a secure plot.','house','rent',850,'Karongi',2,2,180,'h10.jpg','Parking,Garden,Water tank,Security,Fenced compound',-2.06,29.348,'approved',0),
('Agricultural Land','Land in Nyamagabe, suitable for development or investment, with accessible roads and nearby utilities.','land','sale',230000,'Nyamagabe',NULL,NULL,80000,'p9.jpg','Road access,Electricity nearby,Water nearby,Clear boundaries',-2.484,29.564,'approved',0),
('Family House','A well-built home in Musanze offering comfortable family living space on a secure plot.','house','sale',180000,'Musanze',3,2,320,'h10.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.4995,29.635,'approved',0),
('Modern Apartment','A bright apartment in Rubavu with modern finishes, reliable utilities and easy access to the city.','apartment','sale',120000,'Rubavu',2,2,180,'h3.jpg','Elevator,Parking,Backup power,Security,Internet-ready',-1.6792,29.2597,'approved',0),
('Executive Home','A well-built home in Huye offering comfortable family living space on a secure plot.','house','sale',340000,'Huye',5,4,600,'h4.jpg','Parking,Garden,Water tank,Security,Fenced compound',-2.5967,29.7394,'approved',0),
('Town House','A well-built home in Nyamata offering comfortable family living space on a secure plot.','house','sale',150000,'Nyamata',3,2,280,'h5.jpg','Parking,Garden,Water tank,Security,Fenced compound',-2.15,30.0833,'approved',0),
('Luxury Mansion','A well-built home in Kigali offering comfortable family living space on a secure plot.','house','sale',850000,'Kigali',6,5,950,'h6.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.9441,30.0619,'approved',0),
('Luxury Apartment, Kigali Heights','A bright apartment in Kigali with modern finishes, reliable utilities and easy access to the city.','apartment','rent',850,'Kigali',3,2,210,'h13.jpg','Elevator,Parking,Backup power,Security,Internet-ready',-1.9441,30.0619,'approved',0),
('Modern Villa','A well-built home in Musanze offering comfortable family living space on a secure plot.','house','rent',1200,'Musanze',4,3,350,'h11.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.4995,29.635,'approved',0),
('Family House, Rubavu','A well-built home in Rubavu offering comfortable family living space on a secure plot.','house','rent',700,'Rubavu',3,2,240,'h3.jpg','Parking,Garden,Water tank,Security,Fenced compound',-1.6792,29.2597,'approved',0);
