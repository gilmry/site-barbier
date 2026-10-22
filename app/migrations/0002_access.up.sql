-- Accès (FR-040 à FR-043) : compte du gérant, codes de secours, sessions en base, état de l'application.
CREATE TABLE editor (
    id                 CHAR(36)     NOT NULL,
    email              VARCHAR(190) NOT NULL,
    password_hash      VARCHAR(255) NOT NULL,
    totp_secret_sealed VARBINARY(200) NULL,
    totp_confirmed     TINYINT(1)   NOT NULL DEFAULT 0,
    last_totp_step     BIGINT       NULL,
    failed_attempts    INT          NOT NULL DEFAULT 0,
    locked_until       DATETIME     NULL,
    created_at         DATETIME     NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_editor_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recovery_code (
    editor_id CHAR(36)  NOT NULL,
    code_hash CHAR(64)  NOT NULL,
    used_at   DATETIME  NULL,
    PRIMARY KEY (editor_id, code_hash),
    CONSTRAINT fk_recovery_editor FOREIGN KEY (editor_id) REFERENCES editor (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions d'administration en base : jamais dans le /tmp partagé du mutualisé (FR-041).
CREATE TABLE session (
    id_hash          CHAR(64)    NOT NULL,
    editor_id        CHAR(36)    NOT NULL,
    stage            VARCHAR(20) NOT NULL,
    csrf_token       CHAR(64)    NOT NULL,
    totp_verified_at DATETIME    NULL,
    created_at       DATETIME    NOT NULL,
    last_seen        DATETIME    NOT NULL,
    PRIMARY KEY (id_hash),
    KEY ix_session_editor (editor_id),
    CONSTRAINT fk_session_editor FOREIGN KEY (editor_id) REFERENCES editor (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE app_state (
    state_key   VARCHAR(64)  NOT NULL,
    state_value VARCHAR(255) NOT NULL,
    PRIMARY KEY (state_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
