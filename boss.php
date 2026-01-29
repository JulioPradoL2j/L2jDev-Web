<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

require 'db.php';
$config = require __DIR__ . '/config.php';
require __DIR__ . '/partials/header.php';

date_default_timezone_set('America/Sao_Paulo');

$RAID_PER_PAGE = $config['raid']['limitPage'] ?? 10;
$MIN_RAID_LEVEL = $config['raid']['limitlevel'] ?? 20;
/* =========================
   LOAD BOSS NAMES
========================= */
$bossNames = [];
$stmt = $pdo->query("SELECT id, name, level FROM site_bosses");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $bossNames[(int)$r['id']] = $r;
}

/* =========================
   LOAD GRAND BOSSES
========================= */
$grandBossesRaw = $pdo->query("
  SELECT boss_id, respawn_time, currentHP, currentMP, status
  FROM grandboss_data
  ORDER BY boss_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LOAD RAID BOSSES
========================= */
$raidBosses = $pdo->query("
  SELECT boss_id, respawn_time, currentHp, currentMp
  FROM raidboss_spawnlist
  GROUP BY boss_id
  ORDER BY boss_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

usort($raidBosses, function($a, $b) use ($bossNames) {
  $la = (int)($bossNames[(int)$a['boss_id']]['level'] ?? 0);
  $lb = (int)($bossNames[(int)$b['boss_id']]['level'] ?? 0);

  // level desc
  if ($la !== $lb) return $lb <=> $la;

  // desempate: nome asc (opcional)
  $na = $bossNames[(int)$a['boss_id']]['name'] ?? '';
  $nb = $bossNames[(int)$b['boss_id']]['name'] ?? '';
  return strcasecmp($na, $nb);
});


$raidBosses = array_values(array_filter($raidBosses, function($b) use ($bossNames, $MIN_RAID_LEVEL) {
  $lvl = (int)($bossNames[(int)$b['boss_id']]['level'] ?? 0);
  return $lvl >= $MIN_RAID_LEVEL;
}));

/* =========================
   HELPERS
========================= */
 
function grandBossStatus(int $respawnMs): array
{
    $nowMs = (int)(microtime(true) * 1000);

    // Morto (aguardando respawn)
    if ($respawnMs > $nowMs) {
        return ['Dead', 'dead'];
    }

    // Sem respawn programado = dormindo (não acordado)
    return ['Alive', 'alive'];
}

function raidBossStatus($hp, int $respawnMs): array {
  $nowMs = (int)(microtime(true) * 1000);

  if ($hp !== null && (float)$hp > 0) return ['Alive', 'alive'];
  if ($respawnMs > $nowMs) return ['Respawning', 'respawn'];
  return ['Dead', 'dead'];
}


 

function formatRespawnAt($respawnMs): string
{
    if ((int)$respawnMs <= 0) {
        return '—';
    }

    $sec = (int)($respawnMs / 1000);

    if ($sec <= time()) {
        return '—';
    }

    // timestamp é UTC absoluto → convertemos explicitamente
    $dt = (new DateTimeImmutable('@' . $sec))
        ->setTimezone(new DateTimeZone('America/Sao_Paulo'));

    return $dt->format('d/m/Y H:i');
}



/* =========================
   DEDUPE GRAND BOSSES BY NAME
   (ex.: Frintezza / Scarlet duplicados)
   -> mantemos 1 por nome (o que tem respawn mais "importante")
========================= */
$grandBosses = [];
$seen = [];
/* =========================
   HIDE FRINTEZZA FORMS (SCARLET)
   (apenas Grand Boss)
========================= */
$HIDE_GRAND_BOSS_IDS = [
  29046, 29047, // Scarlet forms (ajuste se necessário)
];

// filtra o RAW (somente grandboss_data)
$grandBossesRawFiltered = [];
foreach ($grandBossesRaw as $b) {
  $id = (int)$b['boss_id'];

  // esconde apenas as forms do Frintezza
  if (in_array($id, $HIDE_GRAND_BOSS_IDS, true)) {
    continue;
  }

  $grandBossesRawFiltered[] = $b;
}


foreach ($grandBossesRawFiltered as $b) {
  $id = (int)$b['boss_id'];
  $info = $bossNames[$id] ?? ['name'=>"Boss #$id",'level'=>'?'];

  $key = mb_strtolower(trim($info['name']));
  if ($key === '') $key = "boss#$id";

  // score: preferir ALIVE (HP>0), depois o que tem respawn maior (mais futuro), depois id menor
  $hp = $b['currentHP'];
  $aliveScore = ($hp !== null && (float)$hp > 0) ? 1000000000000 : 0;
  $resp = (int)$b['respawn_time'];
  $score = $aliveScore + $resp;

  if (!isset($seen[$key])) {
    $seen[$key] = ['score' => $score, 'row' => $b];
  } else {
    if ($score > $seen[$key]['score']) {
      $seen[$key] = ['score' => $score, 'row' => $b];
    }
  }
}

foreach ($seen as $k => $v) {
  $grandBosses[] = $v['row'];
}

// ordena por nome
usort($grandBosses, function($a, $b) use ($bossNames){
  $ia = $bossNames[(int)$a['boss_id']]['name'] ?? '';
  $ib = $bossNames[(int)$b['boss_id']]['name'] ?? '';
  return strcasecmp($ia, $ib);
});
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($config['site']['name']) ?> • Bosses</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="assets/css/style.css">
 

<script>
  window.RAID_PER_PAGE = <?= (int)$RAID_PER_PAGE ?>;
</script>

<script src="assets/js/app.js" defer></script>
</head>
<body>

<audio id="bgMusic" src="assets/audio/theme.mp3" autoplay loop preload="auto"></audio>
<button id="musicToggle" class="music-toggle">🔊</button>
<section class="hero hero-home"></section>
<main>

<section class="hero-info">
<div class="panel-grid">

  <!-- GRAND BOSSES -->
  <div class="panel-card panel-full">
    <div class="boss-header">
      <h3>Grand Bosses</h3>
      <input type="text" class="boss-search" placeholder="Pesquisar boss..." data-target="grand">
    </div>

    <div class="boss-list" id="grand-bosses">
      <?php foreach ($grandBosses as $b):
        $id = (int)$b['boss_id'];
        $info = $bossNames[$id] ?? ['name'=>"Boss #$id",'level'=>'?'];
        [$st,$cls] = grandBossStatus($b['respawn_time']);

        $respawnMs = (int)$b['respawn_time'];
      ?>
      <div class="boss-row" data-name="<?= strtolower($info['name']) ?>">
        <div class="boss-main">
          <div class="boss-title">
            <div class="boss-name"><?= htmlspecialchars($info['name']) ?></div>
            <div class="boss-sub">
              <span>Lv <?= htmlspecialchars((string)$info['level']) ?></span>
 
            </div>
          </div>

          <div class="boss-right">
            <span class="boss-chip <?= $cls ?>"><?= $st ?></span>
            <span class="boss-chip countdown" data-respawn-ms="<?= $respawnMs ?>"></span>
          </div>
        </div>

        <div class="boss-extra">
          <div class="boss-box">
            <span class="k">Respawn (data)</span>
            <span class="v"><?= htmlspecialchars(formatRespawnAt($respawnMs)) ?></span>
          </div>
          <div class="boss-box">
            <span class="k">HP / MP</span>
            <span class="v">
              HP: <?= number_format($b['currentHP'] ?? 0,0,',','.') ?> •
              MP: <?= number_format($b['currentMP'] ?? 0,0,',','.') ?>
            </span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- RAID BOSSES -->
  <div class="panel-card panel-full">
    <div class="boss-header">
      <h3>Raid Bosses</h3>
      <input type="text" class="boss-search" placeholder="Pesquisar boss..." data-target="raid">
    </div>

    <div class="boss-list" id="raid-bosses">
      <?php foreach ($raidBosses as $b):
        $id = (int)$b['boss_id'];
        $info = $bossNames[$id] ?? ['name'=>"Boss #$id",'level'=>'?'];
       [$st,$cls] = raidBossStatus($b['currentHp'], (int)$b['respawn_time']);


        $respawnMs = (int)$b['respawn_time'];
      ?>
      <div class="boss-row" data-name="<?= strtolower($info['name']) ?>">
        <div class="boss-main">
          <div class="boss-title">
            <div class="boss-name"><?= htmlspecialchars($info['name']) ?></div>
            <div class="boss-sub">
              <span>Lv <?= htmlspecialchars((string)$info['level']) ?></span>
            </div>
          </div>

          <div class="boss-right">
            <span class="boss-chip <?= $cls ?>"><?= $st ?></span>
            <span class="boss-chip countdown" data-respawn-ms="<?= $respawnMs ?>"></span>
          </div>
        </div>

        <div class="boss-extra">
          <div class="boss-box">
            <span class="k">Respawn (data)</span>
            <span class="v"><?= htmlspecialchars(formatRespawnAt($respawnMs)) ?></span>
          </div>
          <div class="boss-box">
            <span class="k">HP / MP</span>
            <span class="v">
              HP: <?= number_format($b['currentHp'] ?? 0,0,',','.') ?> •
              MP: <?= number_format($b['currentMp'] ?? 0,0,',','.') ?>
            </span>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

    <div class="boss-pager" id="raid-pager">
      <div class="info" id="raid-page-info"></div>
      <div class="nav" style="display:flex; gap:10px;">
        <button class="boss-btn" id="raid-prev" type="button">◀ Anterior</button>
        <button class="boss-btn" id="raid-next" type="button">Próximo ▶</button>
      </div>
    </div>
  </div>

</div>
</section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
