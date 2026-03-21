/**
 * Entry point de Vite.
 * Carga el CSS global e importa los módulos JS según data-page del body.
 */
import '../css/main.css'

const page = document.body.dataset.page

// ── Auth ──────────────────────────────────────────────────────────────────────
if (page === 'login')                 import('./modules/auth.js')
if (page === 'staff-login')           import('./modules/staffAuth.js')
if (page === 'reset-password')        import('./modules/resetPassword.js')
if (page === 'estudiante-onboarding') import('./modules/estudianteOnboarding.js')

// ── Admin ─────────────────────────────────────────────────────────────────────
if (page === 'admin-dashboard')   import('./modules/adminDashboard.js')
if (page === 'admin-usuarios')    import('./modules/adminUsuarios.js')
if (page === 'admin-grupos')      import('./modules/adminGrupos.js')
if (page === 'admin-materias')    import('./modules/adminMaterias.js')
if (page === 'admin-estudiantes') import('./modules/adminEstudiantes.js')

// ── Docente ───────────────────────────────────────────────────────────────────
if (page === 'docente-dashboard')  import('./modules/docenteDashboard.js')
if (page === 'docente-asistencia') import('./modules/docenteAsistencia.js')
if (page === 'docente-historial')  import('./modules/docenteHistorial.js')

// ── Estudiante ────────────────────────────────────────────────────────────────
if (page === 'estudiante-dashboard') import('./modules/estudianteDashboard.js')
if (page === 'estudiante-historial') import('./modules/estudianteDashboard.js')

// ── Control Escolar ───────────────────────────────────────────────────────────
if (page === 'ce-inscripciones') import('./modules/ceInscripciones.js').then(m => m.init())
if (page === 'ce-consultas')     import('./modules/ceConsultas.js')
if (page === 'ce-reportes')      import('./modules/ceReportes.js')

// ── Legacy (módulos existentes) ───────────────────────────────────────────────
if (page === 'attendance') import('./modules/attendance.js')
if (page === 'queries')    import('./modules/queries.js')
if (page === 'dashboard')  import('./modules/dashboard.js')
