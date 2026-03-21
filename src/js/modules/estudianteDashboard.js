import { requestJson } from '../api.js'
import QRCode from 'qrcode'

const BASE = '/modules/estudiante/controllers/misAsistenciasModel.php'

// ── QR Modal ───────────────────────────────────────────────────────────────────
const modal          = document.getElementById('qrModal')
const modalBg        = document.getElementById('qrModalBg')
const modalClose     = document.getElementById('qrModalClose')
const modalMateria   = document.getElementById('qrModalMateria')
const modalCanvas    = document.getElementById('qrModalCanvas')
const modalMatricula = document.getElementById('qrModalMatricula')

function openQR(matricula, idGM, nombreMateria) {
  if (!modal) return
  modal.classList.remove('hidden')
  modalMateria.textContent   = nombreMateria
  modalMatricula.textContent = matricula
  modalCanvas.innerHTML      = '<div class="text-text-muted text-xs">Generando…</div>'

  // El QR codifica "matricula:idGM" para que el docente identifique alumno+materia
  const qrData = `${matricula}:${idGM}`
  QRCode.toCanvas(document.createElement('canvas'), qrData, { width: 200, margin: 1 }, (err, canvas) => {
    if (!err) { modalCanvas.innerHTML = ''; modalCanvas.appendChild(canvas) }
  })
}

function closeQR() { modal?.classList.add('hidden') }

modalBg?.addEventListener('click',    closeQR)
modalClose?.addEventListener('click', closeQR)
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeQR() })

// ── Dashboard ─────────────────────────────────────────────────────────────────
async function init() {
  try {
    const data = await requestJson(`${BASE}?accion=resumen`)

    if (data.error) {
      document.getElementById('gridMaterias').innerHTML =
        `<div class="card text-center text-error text-sm py-6">${data.error}</div>`
      return
    }

    if (data.sin_grupo || data.sin_horario) {
      document.getElementById('gridMaterias').innerHTML =
        `<div class="card text-center text-sm py-8">
           <p class="font-semibold text-warning mb-2">Sin materias asignadas</p>
           <p class="text-text-muted">Control Escolar aún no ha cargado tu horario en el sistema.<br/>
             Si ya te reinscribiste, acude a Control Escolar para que registren tus materias.</p>
         </div>`
      return
    }

    const matricula = data.alumno?.matricula || ''
    const materias  = data.materias || []

    document.getElementById('alertaGeneral')?.classList.toggle('hidden', !materias.some(m => m.alerta))

    const grid = document.getElementById('gridMaterias')
    if (!materias.length) {
      grid.innerHTML = '<div class="card text-center text-text-muted text-sm py-8">Aún no hay clases registradas en tu grupo.</div>'
      return
    }

    grid.innerHTML = materias.map(m => {
      const pctColor = m.pct >= 80 ? 'text-success' : m.pct >= 70 ? 'text-warning' : 'text-error'
      const barColor = m.pct >= 80 ? 'bg-success'   : m.pct >= 70 ? 'bg-warning'   : 'bg-error'
      const border   = m.alerta ? 'border-l-4 border-l-error' : ''
      return `
        <div class="card ${border}">
          <div class="flex items-start justify-between mb-2">
            <p class="font-semibold text-text text-sm flex-1 pr-2">${m.materia}</p>
            <button class="btn-qr shrink-0 flex items-center gap-1 text-xs font-medium text-primary bg-blue-50 hover:bg-blue-100 px-2.5 py-1 rounded-lg transition-colors"
                    data-matricula="${matricula}" data-idgm="${m.idGM}" data-materia="${m.materia}">
              <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
              </svg>
              QR
            </button>
          </div>
          <p class="text-[10px] text-text-muted mb-3">${m.ciclo || ''}</p>
          <div class="flex items-end justify-between mb-2">
            <p class="text-4xl font-bold leading-none ${pctColor}">${m.pct}<span class="text-lg">%</span></p>
            <div class="text-right text-xs text-text-muted space-y-0.5">
              <p class="text-success font-medium">${m.presentes} presentes</p>
              <p>${m.faltas} faltas · ${m.retardos} retardos</p>
            </div>
          </div>
          <div class="progress-bar">
            <div class="progress-fill ${barColor}" style="width:${m.pct}%"></div>
          </div>
          <p class="text-[10px] text-text-muted mt-1.5">Mínimo: ${m.pct_min}%</p>
        </div>`
    }).join('')

    // Bind QR buttons
    grid.querySelectorAll('.btn-qr').forEach(btn => {
      btn.addEventListener('click', () => {
        openQR(btn.dataset.matricula, btn.dataset.idgm, btn.dataset.materia)
      })
    })

  } catch(e) {
    console.error(e)
  }
}

// ── Historial ─────────────────────────────────────────────────────────────────
async function initHistorial() {
  try {
    const mats = await requestJson(`${BASE}?accion=mis_materias`)
    const sel  = document.getElementById('filtroMateriaEst')
    mats.forEach(m => {
      const opt = document.createElement('option')
      opt.value = m.idGM; opt.textContent = m.materia
      sel?.appendChild(opt)
    })
  } catch {}

  async function buscar() {
    const idGM     = document.getElementById('filtroMateriaEst')?.value   || ''
    const fechaIni = document.getElementById('filtroFechaIniEst')?.value  || ''
    const fechaFin = document.getElementById('filtroFechaFinEst')?.value  || ''
    const params   = new URLSearchParams({ accion: 'historial', idGM, fechaIni, fechaFin })

    const lista = document.getElementById('listaHistorial')
    lista.innerHTML = '<p class="text-center text-text-muted text-sm py-6">Cargando…</p>'

    try {
      const rows = await requestJson(`${BASE}?${params}`)
      if (!rows.length) {
        lista.innerHTML = '<p class="text-center text-text-muted text-sm py-8">Sin resultados para los filtros aplicados.</p>'
        return
      }
      const BADGES = { presente: 'badge-success', falta: 'badge-error', retardo: 'badge-warning' }
      lista.innerHTML = rows.map(r => `
        <div class="card py-3 px-4 flex items-center justify-between gap-3">
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-text truncate">${r.materia}</p>
            <p class="text-xs text-text-muted">${r.grupo} · ${r.fecha}${r.hora ? ' ' + r.hora : ''}</p>
          </div>
          <span class="badge ${BADGES[r.estado] || ''} shrink-0">${r.estado}</span>
        </div>`).join('')
    } catch {}
  }

  document.getElementById('btnBuscarEst')?.addEventListener('click', buscar)
}

// ── Init ──────────────────────────────────────────────────────────────────────
const page = document.body.dataset.page
if (page === 'estudiante-dashboard') init()
if (page === 'estudiante-historial') initHistorial()
