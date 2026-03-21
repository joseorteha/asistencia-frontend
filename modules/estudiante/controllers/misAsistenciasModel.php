<?php
require_once __DIR__ . '/../../../config/auth_check.php';
require_once __DIR__ . '/../../../config/role_check.php';
requireRole(['estudiante','admin']);
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$conn      = getConnection();
$idUsuario = (int)$_SESSION['user_id'];
$accion    = $_GET['accion'] ?? 'resumen';

// Obtener alumno vinculado al usuario
$stmt = $conn->prepare(
    "SELECT id, nombre, matricula, idGrupo, tipo_alumno FROM Alumnos WHERE idUsuario=? LIMIT 1"
);
$stmt->bind_param('i', $idUsuario);
$stmt->execute();
$alumno = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$alumno) {
    echo json_encode(['error' => 'sin_registro']);
    exit;
}

$idAlumno    = $alumno['id'];
$cicloActivo = $conn->query("SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1")->fetch_row()[0] ?? '2026-A';

// ── RESUMEN POR MATERIA ──────────────────────────────────────────────────────
// Lee desde Inscripciones, no desde idGrupo, para soportar irregulares.
if ($accion === 'resumen') {
    // Verificar que el alumno tiene materias inscritas
    $stCheck = $conn->prepare(
        "SELECT COUNT(*) FROM Inscripciones WHERE idAlumno=? AND ciclo=?"
    );
    $stCheck->bind_param('is', $idAlumno, $cicloActivo);
    $stCheck->execute();
    $totalInscritas = (int)$stCheck->get_result()->fetch_row()[0];

    if ($totalInscritas === 0) {
        echo json_encode([
            'alumno'      => $alumno,
            'materias'    => [],
            'sin_horario' => true,   // Control Escolar aún no ha asignado materias
        ]);
        exit;
    }

    $r      = $conn->query("SELECT valor FROM Configuracion WHERE clave='porcentaje_minimo' LIMIT 1");
    $pctMin = $r ? (int)$r->fetch_row()[0] : 80;

    // Materias inscritas + sus stats de asistencia
    $stmt = $conn->prepare(
        "SELECT m.nombre AS materia, m.clave AS clave_materia,
                gm.id AS idGM, gm.ciclo, gm.dias,
                TIME_FORMAT(gm.horaInicio,'%H:%i') AS hora_inicio,
                TIME_FORMAT(gm.horaFin,'%H:%i')    AS hora_fin,
                g.nombre AS grupo, g.carrera, g.semestre, g.modalidad,
                u.nombre AS docente,
                COUNT(a.id)                          AS total,
                COALESCE(SUM(a.estado='presente'),0) AS presentes,
                COALESCE(SUM(a.estado='falta'),0)    AS faltas,
                COALESCE(SUM(a.estado='retardo'),0)  AS retardos
         FROM Inscripciones i
         JOIN GruposMaterias gm ON gm.id = i.idGrupoMateria
         JOIN Materias m        ON m.id  = gm.idMateria
         JOIN Grupos   g        ON g.id  = gm.idGrupo
         JOIN Usuarios u        ON u.id  = gm.idDocente
         LEFT JOIN Asistencias a ON a.idGrupoMateria = gm.id AND a.idAlumno = ?
         WHERE i.idAlumno = ? AND i.ciclo = ? AND gm.activo = 1
         GROUP BY gm.id
         ORDER BY m.nombre"
    );
    $stmt->bind_param('iis', $idAlumno, $idAlumno, $cicloActivo);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    foreach ($rows as &$row) {
        $row['pct']     = $row['total'] > 0
            ? round(100 * $row['presentes'] / $row['total'], 1) : 0;
        $row['alerta']  = $row['pct'] < $pctMin;
        $row['pct_min'] = $pctMin;
    }

    echo json_encode([
        'alumno'   => $alumno,
        'materias' => $rows,
    ]);
    exit;
}

// ── HISTORIAL COMPLETO ───────────────────────────────────────────────────────
if ($accion === 'historial') {
    $idGM     = (int)($_GET['idGM'] ?? 0);
    $fechaIni = $_GET['fechaIni'] ?? date('Y-m-01');
    $fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

    $sql = "SELECT m.nombre AS materia, g.nombre AS grupo, g.carrera,
                   a.estado, a.fecha, TIME_FORMAT(a.hora,'%H:%i') AS hora
            FROM Asistencias a
            JOIN GruposMaterias gm ON gm.id = a.idGrupoMateria
            JOIN Materias m        ON m.id  = gm.idMateria
            JOIN Grupos   g        ON g.id  = gm.idGrupo
            -- Asegurar que el alumno está inscrito en esta materia
            JOIN Inscripciones i   ON i.idAlumno = a.idAlumno AND i.idGrupoMateria = a.idGrupoMateria
            WHERE a.idAlumno = ? AND a.fecha BETWEEN ? AND ?";

    $types  = 'iss';
    $params = [$idAlumno, $fechaIni, $fechaFin];

    if ($idGM) {
        $sql   .= " AND a.idGrupoMateria=?";
        $types .= 'i';
        $params[] = $idGM;
    }
    $sql .= " ORDER BY a.fecha DESC, m.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ── MIS MATERIAS (selector para filtros) ─────────────────────────────────────
if ($accion === 'mis_materias') {
    $stmt = $conn->prepare(
        "SELECT gm.id AS idGM, m.nombre AS materia, g.nombre AS grupo
         FROM Inscripciones i
         JOIN GruposMaterias gm ON gm.id = i.idGrupoMateria
         JOIN Materias m        ON m.id  = gm.idMateria
         JOIN Grupos   g        ON g.id  = gm.idGrupo
         WHERE i.idAlumno = ? AND i.ciclo = ?
         ORDER BY m.nombre"
    );
    $stmt->bind_param('is', $idAlumno, $cicloActivo);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

echo json_encode([]);
