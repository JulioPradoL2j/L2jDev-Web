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

$dl = $config['downloads'] ?? [];
$items = $dl['items'] ?? [];
$reqs  = $dl['requirements'] ?? [];
$notes = $dl['notes'] ?? [];

$discord = $config['site']['discord'] ?? null;
$whats   = $config['site']['whatsapp_link'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($config['site']['name']); ?> • Download</title>
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

<div class="download-wrap">

  <!-- HEADER -->
  <div class="download-header">
    <h2><?= htmlspecialchars($dl['title'] ?? 'Downloads Oficiais') ?></h2>
    <p>
      <?= htmlspecialchars($dl['subtitle'] ?? 'Lineage II Interlude clássico (2003–2006), agora com infraestrutura moderna em 2026.') ?>
    </p>
  </div>

  <!-- GRID DE DOWNLOADS -->
  <div class="download-grid">
    <?php foreach ($items as $it):
      $name = $it['name'] ?? 'Arquivo';
      $desc = $it['desc'] ?? '';
      $tag  = $it['tag'] ?? '';
      $size = $it['size'] ?? '';
      $url  = $it['url'] ?? '#';
      $icon = $it['icon'] ?? '⬇';
      $primary = !empty($it['primary']);
    ?>
      <div class="download-card <?= $primary ? 'is-primary' : '' ?>">
        <div class="download-card-head">
          <span class="download-icon"><?= htmlspecialchars($icon) ?></span>
          <span class="download-title"><?= htmlspecialchars($name) ?></span>
        </div>

        <div class="download-desc">
          <?= htmlspecialchars($desc) ?>
        </div>

        <div class="download-meta">
          <span><?= htmlspecialchars($tag ?: 'Download') ?></span>
          <span><?= htmlspecialchars($size ?: '-') ?></span>
        </div>

        <a href="<?= htmlspecialchars($url) ?>"
           target="_blank" rel="noopener"
           class="download-btn">
           Baixar agora
        </a>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- COMO INSTALAR -->
  <div class="download-box">
    <h3>Como instalar</h3>
    <ol class="download-steps">
      <li>Baixe o <b>Updater</b> (recomendado) ou o <b>Cliente Completo</b>.</li>
      <li>Extraia em uma pasta fora de <b>Arquivos de Programas</b>.</li>
      <li>Execute o updater como Administrador na primeira vez.</li>
      <li>Abra o jogo e conecte no servidor.</li>
    </ol>
  </div>

  <!-- PRÉ-REQUISITOS -->
  <?php if (!empty($reqs)): ?>
  <div class="download-box">
    <h3>Pré-requisitos</h3>

    <div class="download-reqs">
      <?php foreach ($reqs as $r): ?>
        <a href="<?= htmlspecialchars($r['url']) ?>"
           target="_blank" rel="noopener"
           class="download-req">
          <strong><?= htmlspecialchars($r['name']) ?></strong>
          <span><?= htmlspecialchars($r['desc']) ?></span>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- DICAS -->
  <?php if (!empty($notes)): ?>
  <div class="download-box">
    <h3>Dicas importantes</h3>
    <ul class="download-notes">
      <?php foreach ($notes as $n): ?>
        <li><?= htmlspecialchars($n) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
  <?php endif; ?>

  <!-- SUPORTE -->
  <div class="download-support">
    <?php if ($discord): ?>
      <a href="<?= htmlspecialchars($discord) ?>" target="_blank" class="support-btn primary">Discord</a>
    <?php endif; ?>

    <?php if ($whats): ?>
      <a href="<?= htmlspecialchars($whats) ?>" target="_blank" class="support-btn">WhatsApp</a>
    <?php endif; ?>
  </div>

</div>


</div>
</section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
