import { requestJson, showMsg } from '../api.js'

const BASE = '/modules/estudiante/controllers/onboardingModel.php'

let avatarSel = 'default'
const prefsSel = new Set()

// ── Avatar picker ─────────────────────────────────────────────────────────────
document.getElementById('avatarGrid')?.querySelectorAll('.avatar-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.avatar-btn').forEach(b => {
      b.classList.remove('border-primary', 'bg-blue-50')
      b.classList.add('border-border')
    })
    btn.classList.add('border-primary', 'bg-blue-50')
    btn.classList.remove('border-border')
    avatarSel = btn.dataset.avatar
    document.getElementById('inputAvatar').value = avatarSel
  })
})

// ── Intereses ─────────────────────────────────────────────────────────────────
document.getElementById('prefsGrid')?.querySelectorAll('.pref-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const p = btn.dataset.pref
    if (prefsSel.has(p)) {
      prefsSel.delete(p)
      btn.classList.remove('border-primary', 'text-primary', 'bg-blue-50')
      btn.classList.add('border-border', 'text-text-muted')
    } else {
      prefsSel.add(p)
      btn.classList.add('border-primary', 'text-primary', 'bg-blue-50')
      btn.classList.remove('border-border', 'text-text-muted')
    }
  })
})

// ── Terminar ──────────────────────────────────────────────────────────────────
document.getElementById('btnTerminar')?.addEventListener('click', async () => {
  const btn      = document.getElementById('btnTerminar')
  const msg      = document.getElementById('msgStep4')
  const nickname = document.getElementById('inputNickname')?.value.trim() ?? ''

  showMsg(msg, 'Guardando…', 'success')
  btn.disabled = true

  const data = new FormData()
  data.append('accion',       'completar_perfil')
  data.append('nickname',     nickname)
  data.append('avatar',       avatarSel)
  data.append('preferencias', JSON.stringify([...prefsSel]))

  try {
    const res = await requestJson(BASE, { method: 'POST', body: data })
    if (res.ok) {
      showMsg(msg, '¡Listo! Cargando tu dashboard…', 'success')
      setTimeout(() => { window.location = '/modules/estudiante/views/dashboard.php' }, 900)
    } else {
      showMsg(msg, res.error || 'Error al guardar. Intenta de nuevo.', 'error')
      btn.disabled = false
    }
  } catch {
    showMsg(msg, 'Error de conexión.', 'error')
    btn.disabled = false
  }
})
