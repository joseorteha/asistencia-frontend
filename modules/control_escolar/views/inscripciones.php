<?php
require_once __DIR__ . '/../../../config/auth_check.php';
require_once __DIR__ . '/../../../config/role_check.php';
requireRole(['admin','control_escolar']);

$title     = 'Inscripciones — ITSZ';
$dataPage  = 'ce-inscripciones';
$activeNav = 'inscripciones';
require_once __DIR__ . '/../../../config/partials/head.php';
?>
<div class="app-layout">
<?php require_once __DIR__ . '/../../../config/partials/sidebar.php'; ?>

<div class="app-main">
  <div class="p-6 lg:p-8">

    <!-- Header -->
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
      <div>
        <h1 class="font-serif text-2xl font-bold text-primary-dark">Inscripciones</h1>
        <p class="text-text-muted text-sm mt-0.5">Asignación de horarios y materias por alumno</p>
      </div>
      <div class="flex gap-2">
        <button id="btnImportSIE"
          class="btn-secondary text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
          </svg>
          Importar SIE
        </button>
        <button id="btnResumenInscritos"
          class="btn-secondary text-sm flex items-center gap-2">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
          </svg>
          Ver resumen
        </button>
      </div>
    </div>

    <!-- Búsqueda de alumno -->
    <div class="card mb-6">
      <h2 class="font-semibold text-text mb-3">Buscar alumno</h2>
      <div class="flex gap-3">
        <input type="text" id="inputBuscarAlumno"
          class="input flex-1 text-sm"
          placeholder="Nombre o matrícula…"
          autocomplete="off" />
        <button id="btnBuscarAlumno" class="btn-primary px-6">Buscar</button>
      </div>
      <!-- Resultados de búsqueda -->
      <div id="resultadosAlumno" class="mt-3 hidden"></div>
    </div>

    <!-- Panel del alumno seleccionado -->
    <div id="panelAlumno" class="hidden">

      <!-- Info del alumno -->
      <div class="card mb-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <p class="text-xs text-text-muted uppercase tracking-wide font-semibold mb-1">Alumno seleccionado</p>
            <h2 id="alumnoNombre" class="font-bold text-primary-dark text-lg"></h2>
            <p id="alumnoMeta" class="text-sm text-text-muted"></p>
          </div>
          <div class="flex items-center gap-3">
            <label class="text-xs font-semibold text-text-muted uppercase tracking-wide">Tipo:</label>
            <select id="selTipoAlumno" class="input text-sm w-auto">
              <option value="regular">Regular</option>
              <option value="irregular">Irregular</option>
              <option value="especial">Especial</option>
            </select>
            <button id="btnGuardarTipo" class="btn-primary text-sm px-4">Guardar</button>
          </div>
        </div>
        <div id="msgTipo" class="msg mt-2"></div>
      </div>

      <!-- Materias inscritas -->
      <div class="card mb-4">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-text">Materias inscritas este ciclo</h3>
          <button id="btnAgregarMateria"
            class="btn-primary text-sm flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Agregar materia
          </button>
        </div>

        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Clave</th>
                <th>Grupo</th>
                <th>Carrera</th>
                <th>Sem.</th>
                <th>Modalidad</th>
                <th>Días / Horario</th>
                <th>Docente</th>
                <th>Origen</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="tablaInscritas">
              <tr><td colspan="10" class="text-center text-text-muted py-8">Sin materias inscritas</td></tr>
            </tbody>
          </table>
        </div>
        <div id="msgInscripciones" class="msg mt-3"></div>
      </div>

    </div>

    <!-- Modal: Agregar materia -->
    <div id="modalAgregar" class="modal-backdrop hidden">
      <div class="modal-box max-w-2xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-text">Agregar materia al alumno</h3>
          <button id="btnCerrarModal" class="text-text-muted hover:text-text">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <!-- Filtros del modal -->
        <div class="grid grid-cols-2 gap-3 mb-4">
          <div>
            <label class="label text-xs">Filtrar por carrera</label>
            <select id="modalFiltroCarrera" class="input text-sm">
              <option value="">Todas las carreras</option>
            </select>
          </div>
          <div>
            <label class="label text-xs">Buscar materia</label>
            <input type="text" id="modalBuscarMateria" class="input text-sm" placeholder="Nombre o clave…" />
          </div>
        </div>

        <div class="table-wrap max-h-80 overflow-y-auto">
          <table class="table text-sm">
            <thead>
              <tr>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Sem.</th>
                <th>Días / Hora</th>
                <th>Docente</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="tablaDisponibles">
              <tr><td colspan="6" class="text-center text-text-muted py-6">Cargando…</td></tr>
            </tbody>
          </table>
        </div>
        <div id="msgModal" class="msg mt-3"></div>
      </div>
    </div>

    <!-- Modal: Importar SIE -->
    <div id="modalSIE" class="modal-backdrop hidden">
      <div class="modal-box max-w-lg">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-text">Importar horarios desde SIE</h3>
          <button id="btnCerrarSIE" class="text-text-muted hover:text-text">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4 text-sm text-blue-800">
          <p class="font-semibold mb-1">Formato CSV esperado:</p>
          <code class="text-xs block mt-1 bg-blue-100 rounded px-2 py-1">
            matricula, sie_id, tipo_alumno, clave_materia, grupo_nombre, ciclo
          </code>
          <ul class="mt-2 text-xs space-y-1 list-disc list-inside">
            <li><strong>tipo_alumno:</strong> regular | irregular | especial</li>
            <li><strong>ciclo</strong> es opcional — usa el ciclo activo si no se especifica</li>
            <li>La primera fila (cabecera) se ignora automáticamente</li>
          </ul>
        </div>

        <div class="mb-4">
          <label class="label text-xs mb-2 block">Archivo CSV del SIE</label>
          <input type="file" id="inputArchivoSIE" accept=".csv" class="input text-sm w-full" />
        </div>

        <button id="btnProcesarSIE" class="btn-primary w-full">
          Procesar e importar
        </button>

        <div id="resultadoSIE" class="mt-4 hidden">
          <div id="msgSIEok" class="hidden bg-green-50 border border-green-200 rounded-lg p-3 text-sm text-green-800"></div>
          <div id="listaSIEerrores" class="hidden mt-2 max-h-40 overflow-y-auto bg-red-50 border border-red-200 rounded-lg p-3 text-xs text-red-700 space-y-1"></div>
        </div>
      </div>
    </div>

    <!-- Modal: Resumen inscritos -->
    <div id="modalResumen" class="modal-backdrop hidden">
      <div class="modal-box max-w-3xl">
        <div class="flex items-center justify-between mb-4">
          <h3 class="font-semibold text-text">Resumen de inscripciones — ciclo activo</h3>
          <button id="btnCerrarResumen" class="text-text-muted hover:text-text">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
          </button>
        </div>
        <div class="table-wrap max-h-96 overflow-y-auto">
          <table class="table text-sm">
            <thead>
              <tr>
                <th>Alumno</th>
                <th>Matrícula</th>
                <th>Tipo</th>
                <th>Carrera</th>
                <th>Sem.</th>
                <th>Materias inscritas</th>
              </tr>
            </thead>
            <tbody id="tablaResumen">
              <tr><td colspan="6" class="text-center text-text-muted py-8">Cargando…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../../../config/partials/app_footer.php'; ?>
