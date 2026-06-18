-- ============================================================
-- MIGRACIÓN FASE 2 - ProyectoEducar
-- Tablas pendientes de migración desde JSON a SQL
-- Ejecutar en phpMyAdmin o consola MySQL sobre la BD: educar_db
-- ============================================================

-- ----------------------------------------
-- TALLERES (reemplaza talleres_dinamicos.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS talleres (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    titulo      VARCHAR(255)  NOT NULL,
    nivel       VARCHAR(100)  DEFAULT '',
    fecha       DATE          NOT NULL,
    hora        TIME          DEFAULT NULL,
    lugar       VARCHAR(255)  DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inscriptos de cada taller (relación N:M simplificada)
CREATE TABLE IF NOT EXISTS taller_inscriptos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    taller_id       INT          NOT NULL,
    alumno_nombre   VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_taller_alumno (taller_id, alumno_nombre),
    FOREIGN KEY (taller_id) REFERENCES talleres(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- CAPACITACIONES (reemplaza capacitaciones.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS capacitaciones (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    titulo  VARCHAR(255) NOT NULL,
    fecha   DATE         NOT NULL,
    hora    TIME         DEFAULT NULL,
    lugar   VARCHAR(255) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inscriptos de cada capacitación
CREATE TABLE IF NOT EXISTS cap_inscriptos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cap_id          INT          NOT NULL,
    docente_nombre  VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_cap_docente (cap_id, docente_nombre),
    FOREIGN KEY (cap_id) REFERENCES capacitaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- ACTIVIDADES (reemplaza actividades.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS actividades (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    profesor         VARCHAR(255) NOT NULL,
    curso            VARCHAR(50)  NOT NULL,
    division         VARCHAR(10)  NOT NULL,
    materia          VARCHAR(100) NOT NULL,
    titulo           VARCHAR(255) NOT NULL,
    descripcion      TEXT         DEFAULT NULL,
    fecha_limite     VARCHAR(50)  DEFAULT NULL,
    archivo_adjunto  VARCHAR(500) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Entregas de alumnos para cada actividad
CREATE TABLE IF NOT EXISTS actividad_entregas (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    actividad_id    INT          NOT NULL,
    alumno_nombre   VARCHAR(255) NOT NULL,
    fecha           VARCHAR(50)  DEFAULT NULL,
    texto           TEXT         DEFAULT NULL,
    archivo         VARCHAR(500) DEFAULT '',
    devolucion      TEXT         DEFAULT NULL,
    nota            VARCHAR(20)  DEFAULT '',
    UNIQUE KEY uq_actividad_alumno (actividad_id, alumno_nombre),
    FOREIGN KEY (actividad_id) REFERENCES actividades(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- RESERVAS DE ESPACIOS (reemplaza reservas.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS reservas (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    espacio  VARCHAR(100) NOT NULL,
    fecha    DATE         NOT NULL,
    modulo   VARCHAR(100) NOT NULL,
    profesor VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_espacio_fecha_modulo (espacio, fecha, modulo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- RECUPERACIONES DE CLAVE (reemplaza recuperaciones.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS recuperaciones (
    id       INT AUTO_INCREMENT PRIMARY KEY,
    usuario  VARCHAR(100) NOT NULL,
    fecha    VARCHAR(30)  NOT NULL,
    UNIQUE KEY uq_usuario (usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- MENÚ DEL COMEDOR (reemplaza menu.json)
-- Tabla de una sola fila: siempre UPDATE sobre id=1
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS menu_comedor (
    id                  INT          NOT NULL DEFAULT 1,
    plato_principal     VARCHAR(255) NOT NULL DEFAULT '',
    opcion_vegetariana  VARCHAR(255) NOT NULL DEFAULT '',
    postre              VARCHAR(255) NOT NULL DEFAULT '',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Fila inicial vacía para que el UPDATE siempre encuentre algo
INSERT IGNORE INTO menu_comedor (id, plato_principal, opcion_vegetariana, postre)
VALUES (1, '', '', '');

-- ----------------------------------------
-- DOCUMENTOS (reemplaza documentos.json)
-- Para tipos "simples" (DNI, Cert. Médico/Apto Físico, etc.):
--   → un solo row por (alumno_nombre, tipo_doc), se reemplaza al subir uno nuevo
-- Para tipos "múltiples" (Cert. Médico/Justificación de Falta, Permiso de Retiro):
--   → múltiples rows permitidos, se acumulan
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS documentos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    alumno_nombre   VARCHAR(255) NOT NULL,
    tipo_doc        VARCHAR(100) NOT NULL,
    fecha           VARCHAR(50)  NOT NULL,
    archivo         VARCHAR(500) NOT NULL,
    estado          VARCHAR(20)  DEFAULT NULL,
    INDEX idx_alumno (alumno_nombre),
    INDEX idx_tipo (tipo_doc)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- TUTORES → ALUMNOS (reemplaza tutores_alumnos.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS tutores_alumnos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    tutor_username  VARCHAR(100) NOT NULL,
    alumno_nombre   VARCHAR(255) NOT NULL,
    UNIQUE KEY uq_tutor_alumno (tutor_username, alumno_nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------------------
-- PATCH: columnas faltantes en rrhh_postulaciones
-- (creada en fase 1 sin fecha_entrevista ni hora_entrevista)
-- ----------------------------------------
ALTER TABLE rrhh_postulaciones
    ADD COLUMN IF NOT EXISTS fecha_entrevista DATE         DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS hora_entrevista  VARCHAR(10)  DEFAULT NULL;

-- ----------------------------------------
-- NIVELES DEL PANEL ADMIN (reemplaza admin/core/datos.json)
-- ----------------------------------------
CREATE TABLE IF NOT EXISTS admin_niveles (
    id           INT          NOT NULL,
    titulo       VARCHAR(100) NOT NULL,
    descripcion  TEXT         DEFAULT NULL,
    imagen       VARCHAR(255) DEFAULT '',
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
