<?php
session_start();
require 'db.php';
$config = require __DIR__ . '/config.php';

require __DIR__ . '/includes/logger.php'; if (!empty($config['debug']['enabled'])) { ini_set('display_errors', '1'); error_reporting(E_ALL); }

require __DIR__ . '/partials/header.php';

$limitTop = $config['ranking']['limit'] ?? 10;

/* =========================
   CONSULTAS
========================= */

// PvP
$topPvp = $pdo->query("
    SELECT char_name, level, pvpkills
    FROM characters
    ORDER BY pvpkills DESC, level DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// PK
$topPk = $pdo->query("
    SELECT char_name, level, pkkills
    FROM characters
    ORDER BY pkkills DESC, level DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// Level
$topLevel = $pdo->query("
    SELECT char_name, level
    FROM characters
    ORDER BY level DESC, lastAccess ASC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// Adena
$topAdena = $pdo->query("
    SELECT c.char_name, SUM(i.count) AS amount
    FROM items i
    JOIN characters c ON c.obj_id = i.owner_id
    WHERE i.item_id = 57
    GROUP BY c.obj_id
    ORDER BY amount DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// Gold Bar
$topGold = $pdo->query("
    SELECT c.char_name, SUM(i.count) AS amount
    FROM items i
    JOIN characters c ON c.obj_id = i.owner_id
    WHERE i.item_id = 3470
    GROUP BY c.obj_id
    ORDER BY amount DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// Jewels
$topJewels = $pdo->query("
    SELECT c.char_name, SUM(i.count) AS amount
    FROM items i
    JOIN characters c ON c.obj_id = i.owner_id
    WHERE i.item_id IN (6656, 6657,6658,6659,6660,6661,6662, 8191)
    GROUP BY c.obj_id
    ORDER BY amount DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// Clan Reputação
$topClanRep = $pdo->query("
    SELECT clan_name, reputation_score
    FROM clan_data
    ORDER BY reputation_score DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);

// Clan Skills
$topClanSkills = $pdo->query("
    SELECT cd.clan_name, COUNT(cs.skill_id) AS skills
    FROM clan_skills cs
    JOIN clan_data cd ON cd.clan_id = cs.clan_id
    GROUP BY cs.clan_id
    ORDER BY skills DESC
    LIMIT $limitTop
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($config['site']['name']); ?> Classificação</title>
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

<!-- =========================
     PvP
========================= -->
<div class="panel-card">
<h3>🏆 Classificação PvP</h3>
<table class="rank-table">
<?php foreach ($topPvp as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['char_name']) ?></td>
<td><?= $r['pvpkills'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- PK -->
<div class="panel-card">
<h3>☠️ Classificação PK</h3>
<table class="rank-table">
<?php foreach ($topPk as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['char_name']) ?></td>
<td><?= $r['pkkills'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Level -->
<div class="panel-card">
<h3>⭐ Classificação Level</h3>
<table class="rank-table">
<?php foreach ($topLevel as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['char_name']) ?></td>
<td><?= $r['level'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Adena -->
<div class="panel-card">
<h3>💰 Classificação Adena</h3>
<table class="rank-table">
<?php foreach ($topAdena as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['char_name']) ?></td>
<td><?= number_format($r['amount']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Gold Bar -->
<div class="panel-card">
<h3>🥇 Classificação Gold Bar</h3>
<table class="rank-table">
<?php foreach ($topGold as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['char_name']) ?></td>
<td><?= number_format($r['amount']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Jewels -->
<div class="panel-card">
<h3>💎 Classificação Jewels</h3>
<table class="rank-table">
<?php foreach ($topJewels as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['char_name']) ?></td>
<td><?= number_format($r['amount']) ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Clan Rep -->
<div class="panel-card">
<h3>🏰 Clan Reputação</h3>
<table class="rank-table">
<?php foreach ($topClanRep as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['clan_name']) ?></td>
<td><?= $r['reputation_score'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

<!-- Clan Skills -->
<div class="panel-card">
<h3>📜 Clan Skills</h3>
<table class="rank-table">
<?php foreach ($topClanSkills as $i => $r): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= htmlspecialchars($r['clan_name']) ?></td>
<td><?= $r['skills'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</div>

</div>
</section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
