-- ═══════════════════════════════════════════════════════════════
--  Migración v4 — Soporte real para estructura TecNM
--
--  Cambios:
--    · Grupos.modalidad       → escolarizado | mixto
--    · Alumnos.tipo_alumno    → regular | irregular | especial
--    · Alumnos.sie_id         → clave interna del SIE (para sync)
--    · Tabla Inscripciones    → alumno ↔ GrupoMateria (muchos-a-muchos)
--                               reemplaza la relación Alumnos.idGrupo para
--                               determinar a qué clases asiste un alumno
--
--  Compatibilidad: idGrupo en Alumnos se conserva como referencia
--  de "grupo base" (carrera/semestre), pero ya NO se usa para validar
--  asistencia. Inscripciones es la fuente de verdad.
--
--  Uso: mysql -u root -p Zongolica < database/migrate_v4_inscripciones.sql
-- ═══════════════════════════════════════════════════════════════

USE Zongolica;

-- ─────────────────────────────────────────
--  1. Modalidad en Grupos
-- ─────────────────────────────────────────
ALTER TABLE Grupos
    ADD COLUMN modalidad ENUM('escolarizado','mixto') NOT NULL DEFAULT 'escolarizado'
    AFTER campus;

-- ─────────────────────────────────────────
--  2. Tipo de alumno + clave SIE
-- ─────────────────────────────────────────
ALTER TABLE Alumnos
    ADD COLUMN tipo_alumno ENUM('regular','irregular','especial') NOT NULL DEFAULT 'regular'
        AFTER onboarding_ok,
    ADD COLUMN sie_id      VARCHAR(40) NULL
        AFTER tipo_alumno,
    ADD INDEX idx_tipo (tipo_alumno),
    ADD INDEX idx_sie  (sie_id);

-- ─────────────────────────────────────────
--  3. Tabla Inscripciones
--     Vincula un alumno con un GrupoMateria específico.
--     Un alumno regular tiene sus materias de su grupo.
--     Un irregular puede tener materias de grupos distintos.
--     Un especial puede tener carga parcial.
-- ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS Inscripciones (
    id             INT         NOT NULL AUTO_INCREMENT,
    idAlumno       INT         NOT NULL,
    idGrupoMateria INT         NOT NULL,
    ciclo          VARCHAR(20) NOT NULL DEFAULT '2026-A',
    -- quién asignó esta inscripción (control_escolar o admin)
    asignado_por   INT         NULL,
    -- origen: manual (control escolar la asignó a mano) o sie (vino del SIE)
    origen         ENUM('manual','sie') NOT NULL DEFAULT 'manual',
    created_at     TIMESTAMP   DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_inscripcion (idAlumno, idGrupoMateria, ciclo),
    INDEX idx_alumno_ins (idAlumno),
    INDEX idx_gm_ins     (idGrupoMateria),
    FOREIGN KEY (idAlumno)       REFERENCES Alumnos(id)          ON DELETE CASCADE,
    FOREIGN KEY (idGrupoMateria) REFERENCES GruposMaterias(id)   ON DELETE CASCADE,
    FOREIGN KEY (asignado_por)   REFERENCES Usuarios(id)         ON DELETE SET NULL
);

-- ─────────────────────────────────────────
--  4. Migración de datos existentes
--     Para alumnos regulares que ya tienen idGrupo asignado,
--     crear automáticamente sus inscripciones con todas las
--     materias activas de ese grupo en el ciclo activo.
-- ─────────────────────────────────────────
INSERT IGNORE INTO Inscripciones (idAlumno, idGrupoMateria, ciclo, origen)
SELECT al.id, gm.id,
       (SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1),
       'manual'
FROM Alumnos al
JOIN GruposMaterias gm ON gm.idGrupo = al.idGrupo AND gm.activo = 1
WHERE al.idGrupo IS NOT NULL
  AND al.activo = 1;

-- ─────────────────────────────────────────
--  5. Actualizar versión
-- ─────────────────────────────────────────
INSERT INTO Configuracion (clave, valor)
VALUES ('version', '4.0')
ON DUPLICATE KEY UPDATE valor='4.0';
