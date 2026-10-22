-- Agenda et réservation (FR-020 à FR-027, FR-030 à FR-032). Heures en UTC.
CREATE TABLE availability (
    id        CHAR(36) NOT NULL,
    barber_id CHAR(36) NOT NULL,
    weekday   TINYINT  NOT NULL,
    starts    TIME     NOT NULL,
    ends      TIME     NOT NULL,
    PRIMARY KEY (id),
    KEY ix_availability_barber (barber_id, weekday),
    CONSTRAINT fk_availability_barber FOREIGN KEY (barber_id) REFERENCES barber (id) ON DELETE CASCADE,
    CONSTRAINT ck_availability_order CHECK (ends > starts),
    CONSTRAINT ck_availability_weekday CHECK (weekday BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Absence d'un barbier, ou fermeture exceptionnelle du salon (barber_id NULL).
CREATE TABLE absence (
    id        CHAR(36) NOT NULL,
    barber_id CHAR(36) NULL,
    starts_at DATETIME NOT NULL,
    ends_at   DATETIME NOT NULL,
    PRIMARY KEY (id),
    KEY ix_absence_range (starts_at, ends_at),
    CONSTRAINT fk_absence_barber FOREIGN KEY (barber_id) REFERENCES barber (id) ON DELETE CASCADE,
    CONSTRAINT ck_absence_order CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE booking (
    id                CHAR(36)     NOT NULL,
    barber_id         CHAR(36)     NOT NULL,
    service_id        CHAR(36)     NOT NULL,
    starts_at         DATETIME     NOT NULL,
    ends_at           DATETIME     NOT NULL,
    price_cents       INT          NOT NULL,
    duration_min      INT          NOT NULL,
    state             VARCHAR(10)  NOT NULL,
    lang              CHAR(2)      NOT NULL,
    client_name       VARCHAR(80)  NOT NULL,
    client_phone      VARCHAR(30)  NOT NULL,
    client_email      VARCHAR(190) NOT NULL,
    cancel_token_hash CHAR(64)     NOT NULL,
    cancelled_by      VARCHAR(10)  NULL,
    created_at        DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_booking_token (cancel_token_hash),
    KEY ix_booking_barber_start (barber_id, starts_at),
    KEY ix_booking_start (starts_at),
    KEY ix_booking_email (client_email),
    CONSTRAINT fk_booking_barber FOREIGN KEY (barber_id) REFERENCES barber (id),
    CONSTRAINT fk_booking_service FOREIGN KEY (service_id) REFERENCES service (id),
    CONSTRAINT ck_booking_state CHECK (state IN ('confirmed', 'cancelled')),
    CONSTRAINT ck_booking_order CHECK (ends_at > starts_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Filet en base contre la double réservation (ADR-006) : une cellule de 5 min ne peut être prise qu'une fois
-- par barbier. Les cellules d'une réservation annulée ou purgée sont libérées dans la même transaction.
CREATE TABLE booking_slot (
    barber_id  CHAR(36) NOT NULL,
    slot_start DATETIME NOT NULL,
    booking_id CHAR(36) NOT NULL,
    PRIMARY KEY (barber_id, slot_start),
    KEY ix_slot_booking (booking_id),
    CONSTRAINT fk_slot_booking FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Limitation de débit (FR-023 @security) : IP hachée avec la clé de l'application, purgée chaque jour.
CREATE TABLE rate_limit (
    bucket       VARCHAR(20) NOT NULL,
    key_hash     CHAR(64)    NOT NULL,
    window_start DATETIME    NOT NULL,
    hits         INT         NOT NULL,
    PRIMARY KEY (bucket, key_hash, window_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Journal des e-mails (FR-032) : jamais le corps ; destinataire masqué à l'affichage ; purgé avec la réservation.
CREATE TABLE mail_log (
    id         CHAR(36)     NOT NULL,
    booking_id CHAR(36)     NULL,
    kind       VARCHAR(30)  NOT NULL,
    recipient  VARCHAR(190) NOT NULL,
    state      VARCHAR(10)  NOT NULL,
    error      VARCHAR(255) NULL,
    attempts   INT          NOT NULL,
    created_at DATETIME     NOT NULL,
    sent_at    DATETIME     NULL,
    PRIMARY KEY (id),
    KEY ix_mail_state (state),
    CONSTRAINT fk_mail_booking FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statistiques anonymes conservées après la purge (O4).
CREATE TABLE booking_stat (
    month     CHAR(7) NOT NULL,
    booked    INT     NOT NULL,
    cancelled INT     NOT NULL,
    PRIMARY KEY (month)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
