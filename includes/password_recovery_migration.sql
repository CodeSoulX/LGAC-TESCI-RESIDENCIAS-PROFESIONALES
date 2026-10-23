CREATE TABLE IF NOT EXISTS recuperacion_tokens (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    token_hash  CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL UNIQUE,
    expires_at  DATETIME NOT NULL,
    used_at     DATETIME NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_recuperacion_usuario (usuario_id),
    INDEX idx_recuperacion_expira (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS recuperacion_solicitudes (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email_hash  CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    ip_hash     CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recuperacion_email_fecha (email_hash, created_at),
    INDEX idx_recuperacion_ip_fecha (ip_hash, created_at),
    INDEX idx_recuperacion_solicitud_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
}