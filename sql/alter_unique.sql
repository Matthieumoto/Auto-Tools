-- MariaDB/phpMyAdmin compatible migration (pas d'index sur expressions)
-- 1) Normaliser les valeurs NULL existantes vers des valeurs par défaut
UPDATE cars SET
  variant = IFNULL(variant, ''),
  fuel    = IFNULL(fuel, 'other'),
  power_kw= IFNULL(power_kw, 0);

-- 2) Forcer des défauts au schéma (évite des NULL futurs)
ALTER TABLE cars
  MODIFY variant VARCHAR(64) NOT NULL DEFAULT '',
  MODIFY fuel ENUM('petrol','diesel','hybrid','electric','other') NOT NULL DEFAULT 'other',
  MODIFY power_kw INT NOT NULL DEFAULT 0;

-- 3) Clé unique composite (identité véhicule pour UPSERT)
ALTER TABLE cars
  ADD UNIQUE KEY ux_cars_identity (make, model, year, variant, fuel, power_kw);

-- 4) Index utile pour les recherches (ignore l'erreur si déjà présent)
CREATE INDEX idx_cars_make_model_year ON cars(make, model, year);