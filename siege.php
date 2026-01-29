<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require 'db.php';
$config = require __DIR__ . '/config.php';

require __DIR__ . '/includes/logger.php';
if (!empty($config['debug']['enabled'])) {
  ini_set('display_errors', '1');
  error_reporting(E_ALL);
}

require __DIR__ . '/partials/header.php';
$MAX_CLANS_PREVIEW = $config['siege']['limit'] ?? 1;

/* =========================
   CONFIG: TABELA DE CLÃ
   Ajuste se necessário:
   - aCis: clan_data (clan_id, clan_name)
   - alguns packs: clan (clan_id, name)
========================= */
$CLAN_TABLE = 'clan_data';
$CLAN_ID_COL = 'clan_id';
$CLAN_NAME_COL = 'clan_name';


/* =========================
   HELPERS
========================= */
function formatAdena($n): string {
  return number_format((int)$n, 0, ',', '.');
}

function epochToDate($epoch): ?DateTime {
  if ($epoch === null) return null;
  $epoch = (string)$epoch;
  if ($epoch === '' || $epoch === '0') return null;

  // Detecta ms (13 dígitos) vs segundos (10 dígitos ou menos)
  $num = (int)$epoch;
  $sec = ($num > 20000000000) ? (int)floor($num / 1000) : $num;

  if ($sec <= 0) return null;

  $dt = new DateTime();
  $dt->setTimestamp($sec);
  return $dt;
}

function formatSiegeDate($epoch): string {
  $dt = epochToDate($epoch);
  if (!$dt) return 'Não definida';
  return $dt->format('d/m/Y H:i');
}

function clanTypeLabel($type): array
{
    switch ((int)$type) {
        case 0:  return ['Defensor', 'badge-defender'];  // no seu core: 0 = defend
        case 1:  return ['Atacante', 'badge-attacker'];  // no seu core: 1 = attack
        default: return ['Atacante', 'badge-attacker'];  // fallback
    }
}


/* =========================
   LOAD CASTLES
========================= */
$castles = $pdo->query("
  SELECT id, name, taxPercent, treasury, siegeDate, regTimeOver, regTimeEnd
  FROM castle
  ORDER BY id ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   LOAD REGISTRATIONS (1 query)
========================= */
$regs = $pdo->query("
  SELECT sc.castle_id, sc.clan_id, sc.type, sc.castle_owner
  FROM siege_clans sc
  ORDER BY sc.castle_id ASC, sc.type ASC, sc.clan_id ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* Agrupa por castelo */
$regsByCastle = [];
$clanIds = [];

foreach ($regs as $r) {
  $cid = (int)$r['castle_id'];
  $regsByCastle[$cid][] = $r;
  $clanIds[(int)$r['clan_id']] = true;
}

$clanNames = [];
if (!empty($clanIds)) {
  $ids = array_keys($clanIds);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));

  // Busca nomes de clans
  $stmt = $pdo->prepare("
    SELECT {$CLAN_ID_COL} AS clan_id, {$CLAN_NAME_COL} AS clan_name
    FROM {$CLAN_TABLE}
    WHERE {$CLAN_ID_COL} IN ($placeholders)
  ");
  $stmt->execute($ids);

  while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $clanNames[(int)$row['clan_id']] = $row['clan_name'];
  }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($config['site']['name']); ?> Siege Castle</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <script src="assets/js/app.js" defer></script>
</head>
<body>

<audio id="bgMusic" src="assets/audio/theme.mp3" autoplay loop preload="auto"></audio>
<button id="musicToggle" class="music-toggle" aria-label="Silenciar música" title="Silenciar música">🔊</button>
 <section class="hero hero-home"></section>

<main>
 
  <section class="hero-info">
    <div class="panel-grid">
      <div class="panel-card" style="width:100%;">
        <h3>Siege Castles</h3>

<div class="siege-grid">
<?php foreach ($castles as $c): 
    $castleId = (int)$c['id'];
    $regList = $regsByCastle[$castleId] ?? [];

    $siegeAt  = formatSiegeDate($c['siegeDate']);
    $regEnd   = formatSiegeDate($c['regTimeEnd']);
    $tax      = (int)$c['taxPercent'];
    $treasury = $c['treasury'];

    // preview logic
    $totalRegs = count($regList);
    $show      = array_slice($regList, 0, $MAX_CLANS_PREVIEW);
    $hasMore   = $totalRegs > $MAX_CLANS_PREVIEW;

    $listId = 'clans_' . $castleId;
?>
  <div class="siege-card">
    <h3><?= htmlspecialchars($c['name']) ?> Castle</h3>

    <div class="siege-meta">
      <div class="item">
        <span class="k">Taxa</span>
        <span class="v"><?= $tax ?>%</span>
      </div>

      <div class="item">
        <span class="k">Tesouro</span>
        <span class="v"><?= formatAdena($treasury) ?></span>
      </div>

      <div class="item">
        <span class="k">Próxima Siege</span>
        <span class="v"><?= htmlspecialchars($siegeAt) ?></span>
      </div>

      <div class="item">
        <span class="k">Registro até</span>
        <span class="v"><?= htmlspecialchars($regEnd) ?></span>
      </div>
    </div>

    <div class="siege-registrations">
      <div class="title">
        <span>Clans registrados</span>
        <span style="opacity:.75; font-size:12px;">
          <?= $totalRegs ?> total
        </span>
      </div>

      <?php if ($totalRegs === 0): ?>
        <div class="siege-empty">Nenhum clan registrado.</div>
      <?php else: ?>

        <!-- PREVIEW -->
        <div class="clan-list" id="<?= $listId ?>">
          <?php foreach ($show as $r):
            $clanId = (int)$r['clan_id'];
            $name   = $clanNames[$clanId] ?? ('Clan #' . $clanId);

            $isOwner = !empty($r['castle_owner']) && (int)$r['castle_owner'] === 1;
            if ($isOwner) {
              $badgeText  = 'Owner';
              $badgeClass = 'badge-owner';
            } else {
              [$badgeText, $badgeClass] = clanTypeLabel($r['type']);
            }
          ?>
            <div class="clan-row">
              <span class="clan-name"><?= htmlspecialchars($name) ?></span>
              <span class="clan-badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeText) ?></span>
            </div>
          <?php endforeach; ?>

          <?php if ($hasMore): ?>
            <button
              class="clan-more"
              type="button"
              data-castle="<?= $castleId ?>"
              data-total="<?= $totalRegs ?>"
            >
              Ver todos (<?= $totalRegs ?>)
            </button>
          <?php endif; ?>
        </div>

        <!-- LISTA COMPLETA (HIDDEN) -->
        <?php if ($hasMore): ?>
          <div class="clan-list is-hidden" id="<?= $listId ?>_all">
            <?php foreach ($regList as $r):
              $clanId = (int)$r['clan_id'];
              $name   = $clanNames[$clanId] ?? ('Clan #' . $clanId);

              $isOwner = !empty($r['castle_owner']) && (int)$r['castle_owner'] === 1;
              if ($isOwner) {
                $badgeText  = 'Owner';
                $badgeClass = 'badge-owner';
              } else {
                [$badgeText, $badgeClass] = clanTypeLabel($r['type']);
              }
            ?>
              <div class="clan-row">
                <span class="clan-name"><?= htmlspecialchars($name) ?></span>
                <span class="clan-badge <?= $badgeClass ?>"><?= htmlspecialchars($badgeText) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      <?php endif; ?>
    </div>
  </div>
<?php endforeach; ?>
</div>


      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
