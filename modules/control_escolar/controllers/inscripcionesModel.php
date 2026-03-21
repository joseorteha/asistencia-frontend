<?php
require_once __DIR__ . '/../../../config/auth_check.php';
require_once __DIR__ . '/../../../config/role_check.php';
requireRole(['admin','control_escolar']);
require_once __DIR__ . '/../../../config/database.php';

header('Content-Type: application/json; charset=utf-8');

$conn        = getConnection();
$rol         = $_SESSION['rol']    ?? '';
$campusScope = ($rol === 'control_escolar') ? ($_SESSION['campus'] ?? '') : '';
$idOperador  = (int)$_SESSION['user_id'];
$accion      = $_GET['accion'] ?? $_POST['accion'] ?? '';

// ── BUSCAR ALUMNO ─────────────────────────────────────────────────────────────
// Devuelve datos básicos del alumno + sus inscripciones actuales
if ($accion === 'buscar_alumno') {
    $q = trim($_GET['q'] ?? '');
    if (strlen($q) < 2) { echo json_encode([]); exit; }

    $like = "%{$q}%";
    $stmt = $conn->prepare(
        "SELECT al.id, al.nombre, al.matricula, al.tipo_alumno, al.sie_id,
                al.activo, g.nombre AS grupo_base, g.carrera, g.semestre,
                u.campus
         FROM Alumnos al
         LEFT JOIN Grupos  g ON g.id = al.idGrupo
         LEFT JOIN Usuarios u ON u.id = al.idUsuario
         WHERE (al.nombre LIKE ? OR al.matricula LIKE ?)
           AND al.activo = 1
         ORDER BY al.nombre LIMIT 20"
    );
    $stmt->bind_param('ss', $like, $like);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ── INSCRIPCIONES DE UN ALUMNO ────────────────────────────────────────────────
if ($accion === 'get_inscripciones') {
    $idAlumno = (int)($_GET['idAlumno'] ?? 0);
    if (!$idAlumno) { echo json_encode([]); exit; }

    $cicloActivo = $conn->query("SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1")->fetch_row()[0] ?? '2026-A';

    $stmt = $conn->prepare(
        "SELECT i.id, i.idGrupoMateria, i.ciclo, i.origen,
                m.nombre AS materia, m.clave AS clave_materia,
                g.nombre AS grupo, g.semestre, g.carrera, g.modalidad,
                gm.dias, TIME_FORMAT(gm.horaInicio,'%H:%i') AS hora_inicio,
                TIME_FORMAT(gm.horaFin,'%H:%i') AS hora_fin,
                u.nombre AS docente
         FROM Inscripciones i
         JOIN GruposMaterias gm ON gm.id = i.idGrupoMateria
         JOIN Materias m        ON m.id  = gm.idMateria
         JOIN Grupos   g        ON g.id  = gm.idGrupo
         JOIN Usuarios u        ON u.id  = gm.idDocente
         WHERE i.idAlumno = ? AND i.ciclo = ?
         ORDER BY m.nombre"
    );
    $stmt->bind_param('is', $idAlumno, $cicloActivo);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ── GRUPOS-MATERIAS DISPONIBLES (para agregar inscripción) ───────────────────
if ($accion === 'get_grupos_materias') {
    $cicloActivo = $conn->query("SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1")->fetch_row()[0] ?? '2026-A';

    $sql = "SELECT gm.id, m.nombre AS materia, m.clave AS clave_materia,
                   g.nombre AS grupo, g.semestre, g.carrera, g.modalidad,
                   gm.dias, TIME_FORMAT(gm.horaInicio,'%H:%i') AS hora_inicio,
                   TIME_FORMAT(gm.horaFin,'%H:%i') AS hora_fin,
                   u.nombre AS docente
            FROM GruposMaterias gm
            JOIN Materias m ON m.id  = gm.idMateria
            JOIN Grupos   g ON g.id  = gm.idGrupo
            JOIN Usuarios u ON u.id  = gm.idDocente
            WHERE gm.activo = 1 AND gm.ciclo = ?";

    $params = [$cicloActivo];
    $types  = 's';

    if ($campusScope) {
        $sql   .= " AND g.campus = ?";
        $types .= 's';
        $params[] = $campusScope;
    }

    $carrera = trim($_GET['carrera'] ?? '');
    if ($carrera) {
        $sql   .= " AND g.carrera = ?";
        $types .= 's';
        $params[] = $carrera;
    }

    $sql .= " ORDER BY g.carrera, g.semestre, m.nombre";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

// ── AGREGAR INSCRIPCIÓN ───────────────────────────────────────────────────────
if ($accion === 'agregar') {
    $idAlumno       = (int)($_POST['idAlumno']       ?? 0);
    $idGrupoMateria = (int)($_POST['idGrupoMateria'] ?? 0);
    $tipo           = $_POST['tipo_alumno'] ?? '';

    if (!$idAlumno || !$idGrupoMateria) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }

    $cicloActivo = $conn->query("SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1")->fetch_row()[0] ?? '2026-A';

    // Actualizar tipo si se envió
    if (in_array($tipo, ['regular','irregular','especial'])) {
        $st = $conn->prepare("UPDATE Alumnos SET tipo_alumno=? WHERE id=?");
        $st->bind_param('si', $tipo, $idAlumno);
        $st->execute();
    }

    $stmt = $conn->prepare(
        "INSERT INTO Inscripciones (idAlumno, idGrupoMateria, ciclo, asignado_por, origen)
         VALUES (?, ?, ?, ?, 'manual')
         ON DUPLICATE KEY UPDATE asignado_por=VALUES(asignado_por), origen='manual'"
    );
    $stmt->bind_param('iisi', $idAlumno, $idGrupoMateria, $cicloActivo, $idOperador);

    if ($stmt->execute()) {
        echo json_encode(['ok'=>true,'msg'=>'Inscripción agregada']);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Error: '.$conn->error]);
    }
    exit;
}

// ── ELIMINAR INSCRIPCIÓN ──────────────────────────────────────────────────────
if ($accion === 'eliminar') {
    $idInscripcion = (int)($_POST['id'] ?? 0);
    if (!$idInscripcion) {
        echo json_encode(['ok'=>false,'msg'=>'ID inválido']); exit;
    }

    $stmt = $conn->prepare("DELETE FROM Inscripciones WHERE id=?");
    $stmt->bind_param('i', $idInscripcion);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo json_encode(['ok'=>true]);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'No encontrado o sin permiso']);
    }
    exit;
}

// ── ACTUALIZAR TIPO DE ALUMNO ─────────────────────────────────────────────────
if ($accion === 'set_tipo') {
    $idAlumno = (int)($_POST['idAlumno'] ?? 0);
    $tipo     = $_POST['tipo'] ?? '';

    if (!$idAlumno || !in_array($tipo, ['regular','irregular','especial'])) {
        echo json_encode(['ok'=>false,'msg'=>'Datos inválidos']); exit;
    }

    $stmt = $conn->prepare("UPDATE Alumnos SET tipo_alumno=? WHERE id=?");
    $stmt->bind_param('si', $tipo, $idAlumno);
    echo json_encode(['ok' => $stmt->execute()]);
    exit;
}

// ── IMPORTAR DESDE SIE (CSV) ──────────────────────────────────────────────────
// Formato CSV esperado del SIE:
//   matricula, sie_id, tipo_alumno, clave_materia, grupo_nombre, ciclo
// El sistema busca el alumno por matrícula y el GrupoMateria por clave+grupo.
// Si no encuentra algún registro lo reporta en errores sin abortar.
if ($accion === 'importar_sie') {
    if (empty($_FILES['archivo']['tmp_name'])) {
        echo json_encode(['ok'=>false,'msg'=>'No se recibió archivo']); exit;
    }

    $cicloActivo = $conn->query("SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1")->fetch_row()[0] ?? '2026-A';

    $handle = fopen($_FILES['archivo']['tmp_name'], 'r');
    if (!$handle) {
        echo json_encode(['ok'=>false,'msg'=>'No se pudo leer el archivo']); exit;
    }

    // Saltar cabecera
    fgetcsv($handle);

    $ok     = 0;
    $errors = [];
    $linea  = 1;

    while (($row = fgetcsv($handle)) !== false) {
        $linea++;
        if (count($row) < 5) {
            $errors[] = "Línea {$linea}: columnas insuficientes";
            continue;
        }

        [$matricula, $sieId, $tipoAlumno, $claveMateria, $grupoNombre] = array_map('trim', $row);
        $ciclo = isset($row[5]) ? trim($row[5]) : $cicloActivo;

        // Validar tipo
        if (!in_array($tipoAlumno, ['regular','irregular','especial'])) {
            $tipoAlumno = 'regular';
        }

        // Buscar alumno
        $stA = $conn->prepare("SELECT id FROM Alumnos WHERE matricula=? LIMIT 1");
        $stA->bind_param('s', $matricula);
        $stA->execute();
        $alumno = $stA->get_result()->fetch_assoc();
        if (!$alumno) {
            $errors[] = "Línea {$linea}: matrícula '{$matricula}' no existe en el sistema";
            continue;
        }
        $idAlumno = $alumno['id'];

        // Actualizar sie_id y tipo
        $stU = $conn->prepare("UPDATE Alumnos SET sie_id=?, tipo_alumno=? WHERE id=?");
        $stU->bind_param('ssi', $sieId, $tipoAlumno, $idAlumno);
        $stU->execute();

        // Buscar GrupoMateria por clave de materia + nombre de grupo + ciclo
        $stGM = $conn->prepare(
            "SELECT gm.id FROM GruposMaterias gm
             JOIN Materias m ON m.id = gm.idMateria
             JOIN Grupos   g ON g.id = gm.idGrupo
             WHERE m.clave = ? AND g.nombre = ? AND gm.ciclo = ? AND gm.activo = 1
             LIMIT 1"
        );
        $stGM->bind_param('sss', $claveMateria, $grupoNombre, $ciclo);
        $stGM->execute();
        $gm = $stGM->get_result()->fetch_assoc();

        if (!$gm) {
            $errors[] = "Línea {$linea}: no se encontró clase para materia '{$claveMateria}' en grupo '{$grupoNombre}' ciclo '{$ciclo}'";
            continue;
        }

        // Insertar inscripción
        $stI = $conn->prepare(
            "INSERT INTO Inscripciones (idAlumno, idGrupoMateria, ciclo, asignado_por, origen)
             VALUES (?, ?, ?, ?, 'sie')
             ON DUPLICATE KEY UPDATE origen='sie', asignado_por=VALUES(asignado_por)"
        );
        $stI->bind_param('iisi', $idAlumno, $gm['id'], $ciclo, $idOperador);
        if ($stI->execute()) {
            $ok++;
        } else {
            $errors[] = "Línea {$linea}: error al guardar ({$conn->error})";
        }
    }

    fclose($handle);
    echo json_encode(['ok'=>true,'importadas'=>$ok,'errores'=>$errors]);
    exit;
}

// ── RESUMEN DE INSCRITOS (para listado general) ───────────────────────────────
if ($accion === 'resumen') {
    $cicloActivo = $conn->query("SELECT valor FROM Configuracion WHERE clave='ciclo_activo' LIMIT 1")->fetch_row()[0] ?? '2026-A';

    $sql = "SELECT al.nombre, al.matricula, al.tipo_alumno,
                   COUNT(i.id) AS materias_inscritas,
                   g.carrera, g.semestre, g.nombre AS grupo_base
            FROM Alumnos al
            LEFT JOIN Grupos g ON g.id = al.idGrupo
            LEFT JOIN Inscripciones i ON i.idAlumno = al.id AND i.ciclo = ?
            WHERE al.activo = 1";
    $params = [$cicloActivo];
    $types  = 's';

    if ($campusScope) {
        $sql   .= " AND EXISTS (SELECT 1 FROM Usuarios u WHERE u.id=al.idUsuario AND u.campus=?)";
        $types .= 's';
        $params[] = $campusScope;
    }

    $sql .= " GROUP BY al.id ORDER BY al.nombre LIMIT 300";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    echo json_encode($stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Acción inválida']);
