<?php
session_start();
require_once __DIR__ . '/../../../config/auth_check.php';
require_once __DIR__ . '/../../../config/role_check.php';
require_once __DIR__ . '/../../../config/database.php';
requireRole(['estudiante']);

header('Content-Type: application/json; charset=utf-8');

$conn   = getConnection();
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

// ── COMPLETAR PERFIL ──────────────────────────────────────────────────────────
// El horario ya está asignado por Control Escolar vía Inscripciones.
// El onboarding solo pide nickname, avatar e intereses.
if ($accion === 'completar_perfil') {
    $idUsuario = (int)$_SESSION['user_id'];
    $nickname  = trim($_POST['nickname'] ?? '');
    $avatar    = trim($_POST['avatar']   ?? 'default');
    $prefs     = json_encode(json_decode($_POST['preferencias'] ?? '[]', true));

    $st = $conn->prepare(
        "UPDATE Alumnos SET nickname=?, avatar=?, preferencias=?, onboarding_ok=1 WHERE idUsuario=?"
    );
    $st->bind_param('sssi', $nickname, $avatar, $prefs, $idUsuario);

    if ($st->execute()) {
        echo json_encode(['ok' => true]);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
    $st->close();
    exit;
}

echo json_encode(['error' => 'Acción no reconocida']);
