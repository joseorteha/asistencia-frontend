// ── Inscripciones — Control Escolar ──────────────────────────────────────────
// Permite asignar materias/horarios individuales por alumno.
// Soporta alumnos regulares, irregulares y especiales.

const BASE = '/modules/control_escolar/controllers/inscripcionesModel.php';

let alumnoActual = null;        // alumno seleccionado
let gmDisponibles = [];         // GruposMaterias cargados en el modal

// ── Utils ─────────────────────────────────────────────────────────────────────
function setMsg(id, texto, tipo = 'error') {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = texto;
  el.className = `msg ${tipo === 'ok' ? 'msg-ok' : 'msg-error'}`;
  el.classList.remove('hidden');
  if (tipo === 'ok') setTimeout(() => el.classList.add('hidden'), 3000);
}

function badgeTipo(tipo) {
  const map = {
    regular:   'badge badge-success',
    irregular: 'badge badge-warning',
    especial:  'badge badge-primary',
  };
  return `<span class="${map[tipo] ?? 'badge'}">${tipo}</span>`;
}

function badgeOrigen(origen) {
  return origen === 'sie'
    ? '<span class="badge badge-primary text-xs">SIE</span>'
    : '<span class="badge text-xs">Manual</span>';
}

// ── Buscar alumno ─────────────────────────────────────────────────────────────
async function buscarAlumno() {
  const q = document.getElementById('inputBuscarAlumno').value.trim();
  if (q.length < 2) return;

  const res  = await fetch(`${BASE}?accion=buscar_alumno&q=${encodeURIComponent(q)}`);
  const data = await res.json();
  const cont = document.getElementById('resultadosAlumno');

  if (!data.length) {
    cont.innerHTML = '<p class="text-sm text-text-muted py-2">Sin resultados.</p>';
    cont.classList.remove('hidden');
    return;
  }

  cont.innerHTML = data.map(a => `
    <button class="result-alumno w-full text-left px-4 py-3 rounded-lg border border-border
                   hover:border-primary hover:bg-blue-50 transition-all mb-2 text-sm"
            data-id="${a.id}" data-nombre="${a.nombre}" data-matricula="${a.matricula}"
            data-tipo="${a.tipo_alumno}" data-carrera="${a.carrera ?? ''}"
            data-semestre="${a.semestre ?? ''}" data-grupo="${a.grupo_base ?? ''}">
      <p class="font-semibold text-text">${a.nombre}</p>
      <p class="text-text-muted text-xs">${a.matricula} · ${badgeTipo(a.tipo_alumno)}</p>
    </button>
  `).join('');
  cont.classList.remove('hidden');

  cont.querySelectorAll('.result-alumno').forEach(btn => {
    btn.addEventListener('click', () => seleccionarAlumno({
      id:         +btn.dataset.id,
      nombre:     btn.dataset.nombre,
      matricula:  btn.dataset.matricula,
      tipo_alumno: btn.dataset.tipo,
      carrera:    btn.dataset.carrera,
      semestre:   btn.dataset.semestre,
      grupo_base: btn.dataset.grupo,
    }));
  });
}

// ── Seleccionar alumno ────────────────────────────────────────────────────────
async function seleccionarAlumno(alumno) {
  alumnoActual = alumno;

  document.getElementById('resultadosAlumno').classList.add('hidden');
  document.getElementById('inputBuscarAlumno').value = alumno.nombre;

  document.getElementById('alumnoNombre').textContent = alumno.nombre;
  document.getElementById('alumnoMeta').textContent =
    `${alumno.matricula} · ${alumno.carrera || 'Sin carrera'} · Sem. ${alumno.semestre || '—'} · Grupo: ${alumno.grupo_base || 'Sin grupo base'}`;

  document.getElementById('selTipoAlumno').value = alumno.tipo_alumno || 'regular';
  document.getElementById('panelAlumno').classList.remove('hidden');

  await cargarInscritas();
}

// ── Cargar materias inscritas ─────────────────────────────────────────────────
async function cargarInscritas() {
  if (!alumnoActual) return;

  const res  = await fetch(`${BASE}?accion=get_inscripciones&idAlumno=${alumnoActual.id}`);
  const data = await res.json();
  const tbody = document.getElementById('tablaInscritas');

  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="10" class="text-center text-text-muted py-8">Sin materias inscritas en el ciclo activo</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(i => `
    <tr>
      <td class="font-medium">${i.materia}</td>
      <td class="font-mono text-xs">${i.clave_materia ?? '—'}</td>
      <td>${i.grupo}</td>
      <td class="text-xs">${i.carrera ?? '—'}</td>
      <td class="text-center">${i.semestre ?? '—'}</td>
      <td><span class="badge ${i.modalidad === 'mixto' ? 'badge-warning' : 'badge-primary'} text-xs">${i.modalidad}</span></td>
      <td class="text-xs">${i.dias ?? '—'} ${i.hora_inicio}–${i.hora_fin}</td>
      <td class="text-xs">${i.docente}</td>
      <td>${badgeOrigen(i.origen)}</td>
      <td>
        <button class="btn-eliminar text-red-500 hover:text-red-700 text-xs font-semibold"
                data-id="${i.id}">Quitar</button>
      </td>
    </tr>
  `).join('');

  tbody.querySelectorAll('.btn-eliminar').forEach(btn => {
    btn.addEventListener('click', () => eliminarInscripcion(+btn.dataset.id));
  });
}

// ── Eliminar inscripción ──────────────────────────────────────────────────────
async function eliminarInscripcion(id) {
  if (!confirm('¿Quitar esta materia del alumno?')) return;

  const form = new FormData();
  form.append('accion', 'eliminar');
  form.append('id', id);

  const res  = await fetch(BASE, { method: 'POST', body: form });
  const data = await res.json();

  if (data.ok) {
    await cargarInscritas();
    setMsg('msgInscripciones', 'Materia removida.', 'ok');
  } else {
    setMsg('msgInscripciones', data.msg || 'Error al eliminar.');
  }
}

// ── Guardar tipo de alumno ────────────────────────────────────────────────────
async function guardarTipo() {
  if (!alumnoActual) return;

  const tipo = document.getElementById('selTipoAlumno').value;
  const form = new FormData();
  form.append('accion', 'set_tipo');
  form.append('idAlumno', alumnoActual.id);
  form.append('tipo', tipo);

  const res  = await fetch(BASE, { method: 'POST', body: form });
  const data = await res.json();

  if (data.ok) {
    alumnoActual.tipo_alumno = tipo;
    setMsg('msgTipo', 'Tipo actualizado.', 'ok');
  } else {
    setMsg('msgTipo', 'Error al actualizar.');
  }
}

// ── Modal: Agregar materia ────────────────────────────────────────────────────
async function abrirModalAgregar() {
  document.getElementById('modalAgregar').classList.remove('hidden');
  document.getElementById('msgModal').classList.add('hidden');
  await cargarDisponibles();
}

async function cargarDisponibles(carrera = '') {
  const tbody = document.getElementById('tablaDisponibles');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-text-muted py-6">Cargando…</td></tr>';

  const url = `${BASE}?accion=get_grupos_materias${carrera ? '&carrera='+encodeURIComponent(carrera) : ''}`;
  const res  = await fetch(url);
  gmDisponibles = await res.json();

  // Poblar filtro de carreras (solo en primera carga)
  const selCarrera = document.getElementById('modalFiltroCarrera');
  if (selCarrera.options.length === 1) {
    const carreras = [...new Set(gmDisponibles.map(g => g.carrera).filter(Boolean))].sort();
    carreras.forEach(c => {
      const opt = document.createElement('option');
      opt.value = c; opt.textContent = c;
      selCarrera.appendChild(opt);
    });
  }

  renderDisponibles(gmDisponibles);
}

function renderDisponibles(lista) {
  const filtro = document.getElementById('modalBuscarMateria').value.toLowerCase();
  const filtrada = filtro
    ? lista.filter(g => g.materia.toLowerCase().includes(filtro) || (g.clave_materia ?? '').toLowerCase().includes(filtro))
    : lista;

  const tbody = document.getElementById('tablaDisponibles');

  if (!filtrada.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-text-muted py-6">Sin resultados</td></tr>';
    return;
  }

  tbody.innerHTML = filtrada.map(g => `
    <tr>
      <td>
        <p class="font-medium text-xs">${g.materia}</p>
        <p class="text-text-muted text-xs">${g.clave_materia ?? ''}</p>
      </td>
      <td class="text-xs">${g.grupo} · Sem ${g.semestre}</td>
      <td class="text-xs text-center">${g.semestre}</td>
      <td class="text-xs">${g.dias ?? '—'}<br/>${g.hora_inicio}–${g.hora_fin}</td>
      <td class="text-xs">${g.docente}</td>
      <td>
        <button class="btn-agregar btn-primary text-xs px-3 py-1" data-id="${g.id}">
          Agregar
        </button>
      </td>
    </tr>
  `).join('');

  tbody.querySelectorAll('.btn-agregar').forEach(btn => {
    btn.addEventListener('click', () => agregarInscripcion(+btn.dataset.id));
  });
}

async function agregarInscripcion(idGrupoMateria) {
  if (!alumnoActual) return;

  const tipo = document.getElementById('selTipoAlumno').value;
  const form = new FormData();
  form.append('accion', 'agregar');
  form.append('idAlumno', alumnoActual.id);
  form.append('idGrupoMateria', idGrupoMateria);
  form.append('tipo_alumno', tipo);

  const res  = await fetch(BASE, { method: 'POST', body: form });
  const data = await res.json();

  if (data.ok) {
    setMsg('msgModal', 'Materia inscrita correctamente.', 'ok');
    await cargarInscritas();
  } else {
    setMsg('msgModal', data.msg || 'Error al inscribir.');
  }
}

// ── Modal: Importar SIE ───────────────────────────────────────────────────────
async function procesarSIE() {
  const inputFile = document.getElementById('inputArchivoSIE');
  if (!inputFile.files.length) {
    alert('Selecciona un archivo CSV primero.'); return;
  }

  const form = new FormData();
  form.append('accion', 'importar_sie');
  form.append('archivo', inputFile.files[0]);

  const btn = document.getElementById('btnProcesarSIE');
  btn.disabled = true;
  btn.textContent = 'Procesando…';

  const res  = await fetch(BASE, { method: 'POST', body: form });
  const data = await res.json();

  btn.disabled = false;
  btn.textContent = 'Procesar e importar';

  const cont = document.getElementById('resultadoSIE');
  cont.classList.remove('hidden');

  if (data.ok) {
    const msgOk = document.getElementById('msgSIEok');
    msgOk.textContent = `Se importaron ${data.importadas} inscripción(es) correctamente.`;
    msgOk.classList.remove('hidden');

    const listErr = document.getElementById('listaSIEerrores');
    if (data.errores?.length) {
      listErr.innerHTML = data.errores.map(e => `<p>⚠ ${e}</p>`).join('');
      listErr.classList.remove('hidden');
    } else {
      listErr.classList.add('hidden');
    }
  }
}

// ── Modal: Resumen ────────────────────────────────────────────────────────────
async function abrirResumen() {
  document.getElementById('modalResumen').classList.remove('hidden');
  const tbody = document.getElementById('tablaResumen');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-text-muted py-8">Cargando…</td></tr>';

  const res  = await fetch(`${BASE}?accion=resumen`);
  const data = await res.json();

  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-text-muted py-8">Sin datos</td></tr>';
    return;
  }

  tbody.innerHTML = data.map(a => `
    <tr>
      <td class="font-medium">${a.nombre}</td>
      <td class="font-mono text-xs">${a.matricula}</td>
      <td>${badgeTipo(a.tipo_alumno)}</td>
      <td class="text-xs">${a.carrera ?? '—'}</td>
      <td class="text-center">${a.semestre ?? '—'}</td>
      <td class="text-center font-semibold ${+a.materias_inscritas === 0 ? 'text-red-500' : 'text-green-600'}">
        ${a.materias_inscritas}
      </td>
    </tr>
  `).join('');
}

// ── Init ──────────────────────────────────────────────────────────────────────
export function init() {
  // Búsqueda de alumno
  document.getElementById('btnBuscarAlumno')?.addEventListener('click', buscarAlumno);
  document.getElementById('inputBuscarAlumno')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') buscarAlumno();
  });

  // Tipo de alumno
  document.getElementById('btnGuardarTipo')?.addEventListener('click', guardarTipo);

  // Modal agregar
  document.getElementById('btnAgregarMateria')?.addEventListener('click', abrirModalAgregar);
  document.getElementById('btnCerrarModal')?.addEventListener('click', () => {
    document.getElementById('modalAgregar').classList.add('hidden');
  });

  // Filtros en modal
  document.getElementById('modalFiltroCarrera')?.addEventListener('change', e => {
    cargarDisponibles(e.target.value);
  });
  document.getElementById('modalBuscarMateria')?.addEventListener('input', () => {
    renderDisponibles(gmDisponibles);
  });

  // Modal SIE
  document.getElementById('btnImportSIE')?.addEventListener('click', () => {
    document.getElementById('resultadoSIE').classList.add('hidden');
    document.getElementById('modalSIE').classList.remove('hidden');
  });
  document.getElementById('btnCerrarSIE')?.addEventListener('click', () => {
    document.getElementById('modalSIE').classList.add('hidden');
  });
  document.getElementById('btnProcesarSIE')?.addEventListener('click', procesarSIE);

  // Modal resumen
  document.getElementById('btnResumenInscritos')?.addEventListener('click', abrirResumen);
  document.getElementById('btnCerrarResumen')?.addEventListener('click', () => {
    document.getElementById('modalResumen').classList.add('hidden');
  });

  // Cerrar modales al hacer clic en backdrop
  document.querySelectorAll('.modal-backdrop').forEach(el => {
    el.addEventListener('click', e => {
      if (e.target === el) el.classList.add('hidden');
    });
  });
}
