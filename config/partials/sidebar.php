<?php
/**
 * Sidebar compartido — se incluye en todas las vistas protegidas.
 * Variables esperadas:
 *   $activeNav — identificador de la sección activa (string)
 * Session:
 *   $_SESSION['rol'], $_SESSION['nombre'], $_SESSION['correo']
 */
$_rol     = $_SESSION['rol']    ?? 'estudiante';
$_nombre  = htmlspecialchars($_SESSION['nombre'] ?? $_SESSION['correo'] ?? '');
$_activeNav = $activeNav ?? '';

$_dashboards = [
    'admin'          => '/modules/admin/views/dashboard.php',
    'docente'        => '/modules/docente/views/dashboard.php',
    'estudiante'     => '/modules/estudiante/views/dashboard.php',
    'control_escolar'=> '/modules/control_escolar/views/dashboard.php',
];
$_dashUrl = $_dashboards[$_rol] ?? '/public/index.php';

function _nav(string $href, string $icon, string $label, string $key, string $active): string
{
    $cls = $key === $active ? 'nav-item active' : 'nav-item';
    return "<a href=\"{$href}\" class=\"{$cls}\">{$icon}<span>{$label}</span></a>";
}

// SVG icons
$i_home     = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>';
$i_users    = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>';
$i_group    = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>';
$i_book     = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>';
$i_check    = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
$i_history  = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
$i_search   = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>';
$i_report   = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
$i_qr       = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>';
$i_logout   = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>';
$i_student  = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>';
?>
<aside class="sidebar">

  <!-- Logo -->
  <div class="sidebar-logo">
    <img src="/public/assets/img/logo-itsz.svg" alt="ITSZ" class="w-9 h-9 object-contain shrink-0" />
    <div class="leading-tight overflow-hidden">
      <p class="text-white text-xs font-bold truncate">Instituto Tecnológico Superior</p>
      <p class="text-blue-400 text-[10px]">Zongolica · TecNM</p>
    </div>
  </div>

  <!-- Navigation -->
  <nav class="sidebar-nav">

    <!-- Dashboard -->
    <?= _nav($_dashUrl, $i_home, 'Dashboard', 'dashboard', $_activeNav) ?>

    <?php if ($_rol === 'admin'): ?>

      <!-- Usuarios -->
      <p class="nav-label">Usuarios</p>
      <?= _nav('/modules/admin/views/usuarios.php',    $i_users,   'Gestión de usuarios', 'usuarios',    $_activeNav) ?>
      <?= _nav('/modules/admin/views/estudiantes.php', $i_student, 'Estudiantes',         'estudiantes', $_activeNav) ?>

      <!-- Académico -->
      <p class="nav-label">Académico</p>
      <?= _nav('/modules/admin/views/grupos.php',   $i_group,  'Grupos',   'grupos',   $_activeNav) ?>
      <?= _nav('/modules/admin/views/materias.php', $i_book,   'Materias', 'materias', $_activeNav) ?>

      <!-- Asistencia -->
      <p class="nav-label">Asistencia</p>
      <?= _nav('/modules/docente/views/asistencia.php', $i_check,   'Registrar',        'asistencia',       $_activeNav) ?>
      <?= _nav('/modules/docente/views/historial.php',  $i_history, 'Historial',        'historial-docente', $_activeNav) ?>

      <!-- Inscripciones -->
      <p class="nav-label">Inscripciones</p>
      <?= _nav('/modules/control_escolar/views/inscripciones.php', $i_book, 'Inscripciones', 'inscripciones', $_activeNav) ?>

      <!-- Consultas -->
      <p class="nav-label">Datos</p>
      <?= _nav('/modules/control_escolar/views/consultas.php', $i_search, 'Consultas', 'consultas', $_activeNav) ?>
      <?= _nav('/modules/control_escolar/views/reportes.php',  $i_report, 'Reportes',  'reportes',  $_activeNav) ?>

    <?php elseif ($_rol === 'docente'): ?>

      <p class="nav-label">Mis clases</p>
      <?= _nav('/modules/docente/views/asistencia.php', $i_check,   'Registrar asistencia', 'asistencia',        $_activeNav) ?>
      <?= _nav('/modules/docente/views/historial.php',  $i_history, 'Historial',            'historial-docente', $_activeNav) ?>

    <?php elseif ($_rol === 'estudiante'): ?>

      <p class="nav-label">Mi asistencia</p>
      <?= _nav('/modules/estudiante/views/dashboard.php', $i_student, 'Resumen',   'dashboard',       $_activeNav) ?>
      <?= _nav('/modules/estudiante/views/historial.php', $i_history, 'Historial', 'historial-alumno', $_activeNav) ?>

    <?php elseif ($_rol === 'control_escolar'): ?>

      <p class="nav-label">Inscripciones</p>
      <?= _nav('/modules/control_escolar/views/inscripciones.php', $i_book, 'Inscripciones', 'inscripciones', $_activeNav) ?>

      <p class="nav-label">Registros</p>
      <?= _nav('/modules/control_escolar/views/consultas.php', $i_search, 'Consultas', 'consultas', $_activeNav) ?>
      <?= _nav('/modules/control_escolar/views/reportes.php',  $i_report, 'Reportes',  'reportes',  $_activeNav) ?>

    <?php endif; ?>

  </nav>

  <!-- User + Logout -->
  <div class="sidebar-foot">
    <div class="flex items-center gap-2 mb-3">
      <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center shrink-0">
        <span class="text-white text-xs font-bold"><?= mb_strtoupper(mb_substr($_nombre, 0, 1)) ?></span>
      </div>
      <div class="overflow-hidden">
        <p class="text-white text-xs font-semibold truncate"><?= $_nombre ?></p>
        <p class="text-blue-400 text-[10px] capitalize"><?= str_replace('_', ' ', $_rol) ?></p>
      </div>
    </div>
    <a href="/modules/auth/controllers/logout.php"
       class="flex items-center gap-2 text-blue-300 hover:text-white text-xs transition-colors">
      <?= $i_logout ?> Cerrar sesión
    </a>
  </div>

</aside>
