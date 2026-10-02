-- =============================================
-- BASE DE DATOS: tesi_lgac
-- Portal del Cuerpo Académico TESI / LGAC
-- =============================================

CREATE DATABASE IF NOT EXISTS tesi_lgac
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE tesi_lgac;

-- ─── USUARIOS (admin y docentes) ──────────────
CREATE TABLE IF NOT EXISTS usuarios (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(120) NOT NULL,
    apellidos   VARCHAR(120) NOT NULL,
    correo      VARCHAR(180) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- bcrypt
    rol         ENUM('admin','docente') NOT NULL DEFAULT 'docente',
    grado       VARCHAR(80),                    -- Dr., M.C., Ing., etc.
    especialidad VARCHAR(200),
    bio         TEXT,
    foto_perfil VARCHAR(255),
    activo      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at  DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── CATEGORÍAS DE PUBLICACIONES ──────────────
CREATE TABLE IF NOT EXISTS categorias (
    id     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    icono  VARCHAR(40) DEFAULT 'folder'         -- nombre de icono (Lucide / FontAwesome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO categorias (nombre, icono) VALUES
('Proyecto de investigación', 'flask-conical'),
('Tesis dirigida',            'graduation-cap'),
('Libro / Capítulo',         'book-open'),
('Artículo / Ponencia',      'file-text'),
('Evento académico',         'calendar'),
('Reconocimiento / Premio',  'award'),
('Fotografías',              'image'),
('Otro',                     'paperclip');

-- ─── PUBLICACIONES ────────────────────────────
CREATE TABLE IF NOT EXISTS publicaciones (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id   INT UNSIGNED NOT NULL,
    categoria_id INT UNSIGNED NOT NULL,
    titulo       VARCHAR(255) NOT NULL,
    descripcion  TEXT,
    anio         YEAR,
    destacado    TINYINT(1) NOT NULL DEFAULT 0,
    visible      TINYINT(1) NOT NULL DEFAULT 1,   -- el docente decide si es pública
    created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id)   REFERENCES usuarios(id)   ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT,
    INDEX idx_usuario   (usuario_id),
    INDEX idx_categoria (categoria_id),
    INDEX idx_destacado (destacado),
    INDEX idx_visible   (visible)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ARCHIVOS ADJUNTOS ────────────────────────
CREATE TABLE IF NOT EXISTS archivos (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    publicacion_id  INT UNSIGNED NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_guardado VARCHAR(255) NOT NULL,        -- nombre en disco (UUID)
    tipo_mime       VARCHAR(100) NOT NULL,
    tamanio         INT UNSIGNED NOT NULL,        -- bytes
    tipo            ENUM('imagen','documento','otro') NOT NULL,
    created_at      DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (publicacion_id) REFERENCES publicaciones(id) ON DELETE CASCADE,
    INDEX idx_publicacion (publicacion_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── SESIONES (tabla propia, más segura) ──────
CREATE TABLE IF NOT EXISTS sesiones (
    token       CHAR(64) PRIMARY KEY,
    usuario_id  INT UNSIGNED NOT NULL,
    ip          VARCHAR(45),
    user_agent  VARCHAR(255),
    expires_at  DATETIME NOT NULL,
    created_at  DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario  (usuario_id),
    INDEX idx_expires  (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─── ADMIN POR DEFECTO ────────────────────────
-- Contraseña inicial: Tesci26! (cámbiala después de instalar)
INSERT INTO usuarios (nombre, apellidos, correo, password, rol)
VALUES (
    'Administrador',
    'TESCI',
    'admin@tesi.edu.mx',
    '$2y$12$N20K4M5/7KJoD.nP1qqQY.weQSc7NyFP4HAF6u08RYBoZMkfdOB3S',
    'admin'
);
