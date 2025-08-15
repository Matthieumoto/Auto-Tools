-- Créer la base et tables de départ
CREATE DATABASE IF NOT EXISTS autotools CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE autotools;

DROP TABLE IF EXISTS cars;
CREATE TABLE cars (
  id INT AUTO_INCREMENT PRIMARY KEY,
  make VARCHAR(64) NOT NULL,
  model VARCHAR(64) NOT NULL,
  year INT NOT NULL,
  variant VARCHAR(64),
  power_kw INT,
  weight_kg INT,
  zero_to_100_s DECIMAL(5,2),
  zero_to_200_s DECIMAL(5,2),
  fuel ENUM('petrol','diesel','hybrid','electric','other') DEFAULT 'petrol',
  co2_gpkm INT,
  wltp_l100 DECIMAL(4,2),
  price_eur INT,
  tax_eur INT,
  image_url VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Données d'exemple (valeurs approximatives à titre de démo)
INSERT INTO cars(make,model,year,variant,power_kw,weight_kg,zero_to_100_s,zero_to_200_s,fuel,co2_gpkm,wltp_l100,price_eur,tax_eur,image_url)
VALUES
('Peugeot','208',2022,'1.2 PureTech 100',74,1150,10.20,NULL,'petrol',118,5.20,22000,0,'https://images.unsplash.com/photo-1542362567-b07e54358753?q=80&w=1200&auto=format&fit=crop'),
('Audi','RS5',2019,'3.0 TFSI Quattro',331,1710,3.90,13.30,'petrol',199,8.70,98000,5000,'https://images.unsplash.com/photo-1525609004556-c46c7d6cf023?q=80&w=1200&auto=format&fit=crop');

-- Index simples
CREATE INDEX idx_make ON cars(make);
CREATE INDEX idx_model ON cars(model);
CREATE INDEX idx_year ON cars(year);