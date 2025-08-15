-- 1) Ajouter la colonne slug (temporairement NULL)
ALTER TABLE cars ADD COLUMN slug VARCHAR(255) NULL AFTER variant;

-- 2) Index unique sur le slug (accepte plusieurs NULL au début)
ALTER TABLE cars ADD UNIQUE KEY ux_cars_slug (slug);

-- 3) (après backfill) Rendre slug NOT NULL (exécuter APRÈS avoir peuplé)
-- ALTER TABLE cars MODIFY slug VARCHAR(255) NOT NULL;