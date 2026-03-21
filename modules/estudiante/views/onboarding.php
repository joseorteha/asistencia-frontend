<?php
session_start();
require_once __DIR__ . '/../../../config/auth_check.php';
require_once __DIR__ . '/../../../config/role_check.php';
requireRole(['estudiante']);
require_once __DIR__ . '/../../../config/database.php';

// Redirigir si ya completó el onboarding
$conn = getConnection();
$st   = $conn->prepare("SELECT onboarding_ok FROM Alumnos WHERE idUsuario=? LIMIT 1");
$uid  = (int)$_SESSION['user_id'];
$st->bind_param('i', $uid);
$st->execute();
$alumno = $st->get_result()->fetch_assoc();
$st->close();

if ($alumno && $alumno['onboarding_ok']) {
    header('Location: /modules/estudiante/views/dashboard.php'); exit;
}

$_nombre = htmlspecialchars($_SESSION['nombre'] ?? '');
$title   = 'Bienvenido — ITSZ';
$dataPage = 'estudiante-onboarding';

$manifest = [];
$manifestPath = __DIR__ . '/../../../public/assets/bundle/.vite/manifest.json';
if (file_exists($manifestPath)) {
    $manifest = json_decode(file_get_contents($manifestPath), true);
}
$cssFile = $manifest['src/js/main.js']['css'][0] ?? null;
$jsFile  = $manifest['src/js/main.js']['file']   ?? null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= htmlspecialchars($title) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700&display=swap" rel="stylesheet" />
  <?php if ($cssFile): ?>
    <link rel="stylesheet" href="/public/assets/bundle/<?= htmlspecialchars($cssFile) ?>">
  <?php endif; ?>
</head>
<body data-page="<?= $dataPage ?>" class="bg-muted min-h-screen flex flex-col">

  <!-- Header -->
  <header class="bg-primary-dark text-white px-5 py-4 flex items-center gap-3 shrink-0">
    <img src="/public/assets/img/logo-itsz.svg" alt="ITSZ" class="w-8 h-8 object-contain" />
    <div>
      <p class="font-bold text-sm">Instituto Tecnológico Superior de Zongolica</p>
      <p class="text-blue-300 text-xs">TecNM · Portal Estudiantil</p>
    </div>
  </header>

  <div class="flex-1 flex items-start justify-center px-4 py-8 pb-16">
    <div class="w-full max-w-md">

      <!-- Bienvenida -->
      <div class="text-center mb-8">
        <p class="text-4xl mb-3">👋</p>
        <h1 class="font-serif text-2xl font-bold text-primary-dark mb-1">
          Hola, <?= $_nombre ?>
        </h1>
        <p class="text-text-muted text-sm">
          Tu horario ya fue asignado por Control Escolar.<br/>
          Solo personaliza tu perfil para continuar.
        </p>
      </div>

      <!-- Perfil -->
      <div class="card">
        <h2 class="font-semibold text-text text-lg mb-1">Personaliza tu perfil</h2>
        <p class="text-xs text-text-muted mb-6">Todo es opcional — puedes cambiarlo después.</p>

        <!-- Nickname -->
        <div class="mb-5">
          <label class="text-xs font-semibold text-text-muted uppercase tracking-wide mb-2 block">
            Apodo / Alias <span class="font-normal normal-case">(opcional)</span>
          </label>
          <input id="inputNickname" type="text" maxlength="40" placeholder="ej. Luisito, La Araña..."
            class="input w-full" />
        </div>

        <!-- Avatar -->
        <div class="mb-5">
          <label class="text-xs font-semibold text-text-muted uppercase tracking-wide mb-3 block">Elige tu avatar</label>
          <div id="avatarGrid" class="grid grid-cols-6 gap-2">
            <?php
            $avatars = [
              ['id'=>'default','emoji'=>'🎓','label'=>'Birrete'],
              ['id'=>'gato',   'emoji'=>'🐱','label'=>'Gato'],
              ['id'=>'zorro',  'emoji'=>'🦊','label'=>'Zorro'],
              ['id'=>'buho',   'emoji'=>'🦉','label'=>'Búho'],
              ['id'=>'aguila', 'emoji'=>'🦅','label'=>'Águila'],
              ['id'=>'lobo',   'emoji'=>'🐺','label'=>'Lobo'],
            ];
            foreach ($avatars as $av): ?>
              <button type="button" data-avatar="<?= $av['id'] ?>"
                class="avatar-btn flex flex-col items-center gap-1 rounded-xl border-2 border-border bg-surface p-2 hover:border-primary transition-all <?= $av['id'] === 'default' ? 'border-primary bg-blue-50' : '' ?>"
                title="<?= $av['label'] ?>">
                <span class="text-2xl leading-none"><?= $av['emoji'] ?></span>
              </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="inputAvatar" value="default" />
        </div>

        <!-- Intereses -->
        <div class="mb-6">
          <label class="text-xs font-semibold text-text-muted uppercase tracking-wide mb-3 block">
            Mis intereses <span class="font-normal normal-case">(elige los que quieras)</span>
          </label>
          <div class="flex flex-wrap gap-2" id="prefsGrid">
            <?php
            $intereses = [
              'Programación','Diseño','Matemáticas','Física','Inglés',
              'Deportes','Música','Emprendimiento','Robótica','Medio Ambiente'
            ];
            foreach ($intereses as $tag): ?>
              <button type="button" data-pref="<?= htmlspecialchars($tag) ?>"
                class="pref-btn text-xs px-3 py-1.5 rounded-full border border-border bg-surface text-text-muted hover:border-primary hover:text-primary transition-all">
                <?= htmlspecialchars($tag) ?>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <button id="btnTerminar"
          class="btn-primary w-full py-3 text-base font-semibold rounded-xl flex items-center justify-center gap-2">
          <span>¡Listo, entrar al sistema!</span>
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
          </svg>
        </button>
        <div id="msgStep4" class="msg mt-4"></div>
      </div>

    </div>
  </div>

  <?php if ($jsFile): ?>
    <script type="module" src="/public/assets/bundle/<?= htmlspecialchars($jsFile) ?>"></script>
  <?php endif; ?>
</body>
</html>
