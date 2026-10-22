-- Vitrine (FR-001 à FR-011) : contenus éditables et traduits. Les textes vivent dans `translation`
-- (une ligne par propriétaire × champ × langue) ; le FR est obligatoire, NL et EN retombent sur le FR.
CREATE TABLE translation (
    owner_type VARCHAR(20)  NOT NULL,
    owner_id   VARCHAR(64)  NOT NULL,
    field      VARCHAR(40)  NOT NULL,
    lang       CHAR(2)      NOT NULL,
    value      TEXT         NOT NULL,
    PRIMARY KEY (owner_type, owner_id, field, lang),
    CONSTRAINT ck_translation_lang CHECK (lang IN ('fr', 'nl', 'en'))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE media (
    id         CHAR(36)     NOT NULL,
    path       VARCHAR(120) NOT NULL,
    mime       VARCHAR(20)  NOT NULL,
    width      INT          NOT NULL,
    height     INT          NOT NULL,
    created_at DATETIME     NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE section (
    section_key VARCHAR(40) NOT NULL,
    page        VARCHAR(20) NOT NULL,
    position    INT         NOT NULL,
    version     INT         NOT NULL DEFAULT 1,
    media_id    CHAR(36)    NULL,
    PRIMARY KEY (section_key),
    CONSTRAINT fk_section_media FOREIGN KEY (media_id) REFERENCES media (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE category (
    id       CHAR(36) NOT NULL,
    position INT      NOT NULL,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE service (
    id           CHAR(36)   NOT NULL,
    category_id  CHAR(36)   NOT NULL,
    price_cents  INT        NOT NULL,
    duration_min INT        NOT NULL,
    published    TINYINT(1) NOT NULL DEFAULT 1,
    position     INT        NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_service_category FOREIGN KEY (category_id) REFERENCES category (id),
    CONSTRAINT ck_service_price CHECK (price_cents >= 0),
    CONSTRAINT ck_service_duration CHECK (duration_min > 0 AND duration_min <= 240 AND duration_min % 5 = 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE barber (
    id             CHAR(36)    NOT NULL,
    first_name     VARCHAR(60) NOT NULL,
    photo_media_id CHAR(36)    NULL,
    active         TINYINT(1)  NOT NULL DEFAULT 1,
    position       INT         NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT fk_barber_photo FOREIGN KEY (photo_media_id) REFERENCES media (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE barber_service (
    barber_id  CHAR(36) NOT NULL,
    service_id CHAR(36) NOT NULL,
    PRIMARY KEY (barber_id, service_id),
    CONSTRAINT fk_bs_barber FOREIGN KEY (barber_id) REFERENCES barber (id) ON DELETE CASCADE,
    CONSTRAINT fk_bs_service FOREIGN KEY (service_id) REFERENCES service (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Horaires du salon : un jour sans ligne est fermé. weekday ISO 1 (lundi) … 7 (dimanche).
CREATE TABLE opening_hours (
    weekday TINYINT NOT NULL,
    opens   TIME    NOT NULL,
    closes  TIME    NOT NULL,
    PRIMARY KEY (weekday),
    CONSTRAINT ck_hours_weekday CHECK (weekday BETWEEN 1 AND 7),
    CONSTRAINT ck_hours_order CHECK (closes > opens)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE salon (
    id    TINYINT      NOT NULL,
    name  VARCHAR(80)  NOT NULL,
    phone VARCHAR(30)  NOT NULL,
    email VARCHAR(190) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_salon_single CHECK (id = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
