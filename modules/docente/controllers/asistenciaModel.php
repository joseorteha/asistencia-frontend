<?php
require_once __DIR__ . '/../../../config/auth_check.php';
require_once __DIR__ . '/../../../config/role_check.php';
requireRole(['admin','docente']);
require_once __DIR__ . '/../../../config/database.php';

$conn      = getConnection();
$idDocente = (int)$_SESSION['user_id'];
$accion    = $_POST['accion'] ?? $_GET['accion'] ?? '';

// ── LISTA DEL DÍA ─────────────────────────────────────────────────────────────
// Usa Inscripciones como fuente de verdad: muestra todos los alumnos
// que están inscritos en este GrupoMateria (regulares, irregulares y especiales).
if ($accion === 'lista_hoy') {
    header('Content-Type: text/html; charset=utf-8');
    $idGM = (int)($_GET['idGM'] ?? 0);
    if (!$idGM) {
        echo '<tr><td colspan="4" class="text-center text-text-muted py-4">Sin clase seleccionada</td></tr>';
        exit;
    }

    $stmt = $conn->prepare(
        "SELECT al.nombre, al.matricula, al.tipo_alumno,
                COALESCE(a.estado,'—') AS estado,
                COALESCE(TIME_FORMAT(a.hora,'%H:%i'),'—') AS hora
         FROM Inscripciones i
         JOIN Alumnos al ON al.id = i.idAlumno AND al.activo = 1
         LEFT JOIN Asistencias a
               ON a.idAlumno = al.id
              AND a.idGrupoMateria = i.idGrupoMateria
              AND a.fecha = CURDATE()
         WHERE i.idGrupoMateria = ?
         ORDER BY al.nombre"
    );
    $stmt->bind_param('i', $idGM);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    if (!$rows) {
        echo '<tr><td colspan="4" class="text-center text-text-muted py-4">Sin alumnos inscritos en esta clase</td></tr>';
        exit;
    }

    $badges = [
        'presente' => 'badge-success',
        'falta'    => 'badge-error',
        'retardo'  => 'badge-warning',
        '—'        => 'badge-primary',
    ];
    $tipoBadge = [
        'irregular' => '<span class="badge badge-warning text-xs ml-1">IRR</span>',
        'especial'  => '<span class="badge badge-primary text-xs ml-1">ESP</span>',
        'regular'   => '',
    ];

    foreach ($rows as $r) {
        $badge = $badges[$r['estado']] ?? 'badge-primary';
        $tipo  = $tipoBadge[$r['tipo_alumno']] ?? '';
        echo "<tr>
          <td>{$r['nombre']}{$tipo}</td>
          <td class='font-mono text-xs'>{$r['matricula']}</td>
          <td><span class='badge {$badge}'>{$r['estado']}</span></td>
          <td class='text-xs text-text-muted'>{$r['hora']}</td>
        </tr>";
    }
    exit;
}

// ── REGISTRAR ASISTENCIA ──────────────────────────────────────────────────────
// Valida que el alumno esté inscrito en el GrupoMateria (tabla Inscripciones),
// no que pertenezca al grupo base. Esto permite irregulares y especiales.
if ($accion === 'registrar') {
    header('Content-Type: application/json; charset=utf-8');
    $idGM      = (int)($_POST['idGM']      ?? 0);
    $matricula = trim($_POST['matricula']  ?? '');
    $estado    = $_POST['estado'] ?? 'presente';

    if (!$idGM || !$matricula) {
        echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
    }
    if (!in_array($estado, ['presente','falta','retardo'])) {
        $estado = 'presente';
    }

    // Buscar alumno y verificar inscripción en este GrupoMateria
    $stmt = $conn->prepare(
        "SELECT al.id, al.nombre, al.tipo_alumno
         FROM Alumnos al
         JOIN Inscripciones i ON i.idAlumno = al.id AND i.idGrupoMateria = ?
         WHERE al.matricula = ? AND al.activo = 1
         LIMIT 1"
    );
    $stmt->bind_param('is', $idGM, $matricula);
    $stmt->execute();
    $alumno = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$alumno) {
        echo json_encode(['ok'=>false,'msg'=>'Matrícula no inscrita en esta clase']); exit;
    }

    $hora  = date('H:i:s');
    $fecha = date('Y-m-d');

    $stmt = $conn->prepare(
        "INSERT INTO Asistencias (idGrupoMateria, idAlumno, estado, fecha, hora, registrado_por)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE estado=VALUES(estado), hora=VALUES(hora)"
    );
    $stmt->bind_param('iisssi', $idGM, $alumno['id'], $estado, $fecha, $hora, $idDocente);

    if ($stmt->execute()) {
        echo json_encode([
            'ok'     => true,
            'nombre' => $alumno['nombre'],
            'estado' => $estado,
            'tipo'   => $alumno['tipo_alumno'],
        ]);
    } else {
        echo json_encode(['ok'=>false,'msg'=>'Error al guardar: '.$conn->error]);
    }
    exit;
}

echo json_encode(['ok'=>false,'msg'=>'Acción inválida']);
