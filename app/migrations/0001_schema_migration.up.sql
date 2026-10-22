-- Table de suivi des migrations appliquées (utilisée par le Migrator, /install et l'admin).
CREATE TABLE schema_migration (
    version    CHAR(4)      NOT NULL,
    name       VARCHAR(100) NOT NULL,
    applied_at DATETIME     NOT NULL,
    PRIMARY KEY (version)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
