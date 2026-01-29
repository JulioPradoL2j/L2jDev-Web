<?php
session_start();
require 'db.php';
date_default_timezone_set('America/Sao_Paulo');

$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Preferência: confiar no banco (mais seguro do que confiar no GET)
$stmt = $pdo->prepare("SELECT blocked_until, reason FROM site_firewall WHERE ip=?");
$stmt->execute([$ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$blockedUntil = (int)($row['blocked_until'] ?? 0);
$reason       = (string)($row['reason'] ?? 'blocked');

// Se não está mais bloqueado, volta ao login
if (!$blockedUntil || $blockedUntil <= time()) {
    header('Location: login.php');
    exit;
}

$remaining = $blockedUntil - time();

function fmtSeconds(int $s): string {
    if ($s < 0) $s = 0;
    $m = intdiv($s, 60);
    $h = intdiv($m, 60);
    $d = intdiv($h, 24);
    $m = $m % 60;
    $h = $h % 24;

    if ($d > 0) return "{$d}d {$h}h {$m}m";
    if ($h > 0) return "{$h}h {$m}m";
    return "{$m}m";
}

function reasonLabel(string $reason): array {
    // você pode expandir isso depois
    switch ($reason) {
        case 'brute_force':
            return [
                'title' => 'Acesso Temporariamente Bloqueado',
                'desc'  => 'Detectamos muitas tentativas de login em sequência. Para proteger sua conta, o acesso foi bloqueado por um período.'
            ];
        default:
            return [
                'title' => 'Acesso Bloqueado',
                'desc'  => 'O acesso foi temporariamente restrito por segurança.'
            ];
    }
}

$info = reasonLabel($reason);
$unlockIso = date('c', $blockedUntil); // usado pelo JS
$unlockHuman = date('d/m/Y H:i:s', $blockedUntil);
$remainingHuman = fmtSeconds($remaining);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($info['title']) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
<style>
 
.blocked-wrap{
  min-height: 100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding: 24px;
}

.blocked-card{
  width:100%;
  max-width: 720px;
  background: linear-gradient(180deg, #121735, #0b0f23);
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 18px;
  box-shadow: 0 30px 110px rgba(0,0,0,.75);
  overflow:hidden;
  position: relative;
}

.blocked-top{
  padding: 22px 22px 14px;
  border-bottom: 1px solid rgba(255,255,255,0.06);
  display:flex;
  align-items:flex-start;
  justify-content:space-between;
  gap: 12px;
}

.blocked-title{
  margin:0;
  font-size: 20px;
  font-weight: 900;
  letter-spacing: .3px;
  color: #ffffff;
}

.blocked-sub{
  margin: 8px 0 0;
  color: #cfd6ff;
  opacity: .9;
  line-height: 1.6;
}

.badge-danger{
  display:inline-flex;
  align-items:center;
  gap:8px;
  padding: 8px 10px;
  border-radius: 12px;
  font-weight: 900;
  font-size: 12px;
  color: #ffd1d1;
  background: rgba(255, 125, 125, 0.10);
  border: 1px solid rgba(255,125,125,0.25);
  white-space: nowrap;
}

.blocked-body{
  padding: 18px 22px 22px;
}

.kv{
  display:grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 12px;
}

.kv-item{
  background: rgba(255,255,255,0.04);
  border: 1px solid rgba(255,255,255,0.06);
  border-radius: 14px;
  padding: 12px 14px;
}

.kv-item .k{
  display:block;
  font-size: 12px;
  color: #cfd6ff;
  opacity: .85;
  margin-bottom: 6px;
}

.kv-item .v{
  display:block;
  font-size: 18px;
  font-weight: 900;
  color: #ffffff;
}

.countdown{
  font-variant-numeric: tabular-nums;
}

.blocked-actions{
  display:flex;
  gap: 10px;
  flex-wrap: wrap;
  margin-top: 16px;
}

.blocked-actions a{
  display:inline-flex;
  align-items:center;
  justify-content:center;
  padding: 10px 14px;
  border-radius: 12px;
  text-decoration:none;
  font-weight: 900;
  letter-spacing: .2px;
  border: 1px solid rgba(255,255,255,.10);
  background: rgba(255,255,255,0.04);
  color: rgba(255,255,255,0.9);
  transition: transform .15s ease, box-shadow .15s ease, background .15s ease;
}

.blocked-actions a:hover{
  transform: translateY(-1px);
  box-shadow: 0 10px 30px rgba(0,0,0,0.35);
  background: rgba(255,255,255,0.07);
}

.blocked-actions .primary{
  background: linear-gradient(180deg, rgba(255,140,0,0.22), rgba(0,0,0,0.10));
  border-color: rgba(255,140,0,0.35);
  color: #ffdf9f;
}

.blocked-actions .disabled{
  opacity: .55;
  pointer-events: none;
}

.note{
  margin-top: 14px;
  font-size: 12px;
  color: #cfd6ff;
  opacity: .8;
  line-height: 1.5;
}

@media (max-width: 640px){
  .kv{ grid-template-columns: 1fr; }
}
</style>
</head>
<body>

<div class="blocked-wrap">
  <div class="blocked-card" role="alert" aria-live="polite">
    <div class="blocked-top">
      <div>
        <h1 class="blocked-title"><?= htmlspecialchars($info['title']) ?></h1>
        <p class="blocked-sub"><?= htmlspecialchars($info['desc']) ?></p>
      </div>

      <div class="badge-danger" title="Restrição temporária">
        Bloqueio Ativo
      </div>
    </div>

    <div class="blocked-body">
      <div class="kv">
        <div class="kv-item">
          <span class="k">Tempo restante</span>
          <span class="v countdown" id="countdown"><?= htmlspecialchars($remainingHuman) ?></span>
        </div>

        <div class="kv-item">
          <span class="k">Liberado em</span>
          <span class="v" id="unlockAt"><?= htmlspecialchars($unlockHuman) ?></span>
        </div>

        <div class="kv-item">
          <span class="k">IP</span>
          <span class="v" style="font-size:14px; font-weight:800; opacity:.95;">
            <?= htmlspecialchars($ip) ?>
          </span>
        </div>

        <div class="kv-item">
          <span class="k">Motivo</span>
          <span class="v" style="font-size:14px; font-weight:800; opacity:.95;">
            <?= htmlspecialchars($reason) ?>
          </span>
        </div>
      </div>

      <div class="blocked-actions">
        <a href="login.php">Voltar ao Login</a>
        <a href="login.php" class="primary disabled" id="retryBtn">Tentar novamente</a>
      </div>

      <div class="note">
        Se você não reconhece essas tentativas, recomendamos trocar sua senha assim que o acesso for liberado.
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const unlockAt = new Date("<?= $unlockIso ?>").getTime();
  const cdEl = document.getElementById('countdown');
  const retryBtn = document.getElementById('retryBtn');

  function pad(n){ return String(n).padStart(2,'0'); }

  function fmt(ms){
    if (ms < 0) ms = 0;
    const s = Math.floor(ms/1000);
    const m = Math.floor(s/60);
    const h = Math.floor(m/60);
    const d = Math.floor(h/24);
    const mm = m % 60;
    const hh = h % 24;
    const ss = s % 60;

    if (d > 0) return `${d}d ${hh}h ${mm}m`;
    if (h > 0) return `${hh}h ${mm}m ${pad(ss)}s`;
    return `${mm}m ${pad(ss)}s`;
  }

  function tick(){
    const now = Date.now();
    const left = unlockAt - now;

    cdEl.textContent = fmt(left);

    if (left <= 0){
      retryBtn.classList.remove('disabled');
      retryBtn.textContent = 'Tentar novamente';
      clearInterval(timer);
    }
  }

  const timer = setInterval(tick, 250);
  tick();
})();
</script>

</body>
</html>
