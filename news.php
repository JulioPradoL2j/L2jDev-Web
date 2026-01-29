<?php
date_default_timezone_set('America/Sao_Paulo');
session_start();
require 'db.php';
$config = require __DIR__ . '/config.php';

$PER_PAGE = $config['news']['limit'] ?? 10;
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$offset = ($page - 1) * $PER_PAGE;

// total
$stmt = $pdo->query("SELECT COUNT(*) FROM site_news WHERE is_published = 1");
$total = (int)$stmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $PER_PAGE));

// clamp
if ($page > $totalPages) {
  $page = $totalPages;
  $offset = ($page - 1) * $PER_PAGE;
}

// list
$stmt = $pdo->prepare("
  SELECT id, title, summary, category, tags, published_at
  FROM site_news
  WHERE is_published = 1
  ORDER BY is_pinned DESC, published_at DESC, id DESC
  LIMIT :limit OFFSET :offset
");
$stmt->bindValue(':limit', $PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$news = $stmt->fetchAll(PDO::FETCH_ASSOC);

function newsMetaLine(array $n): string {
  $dt = $n['published_at'] ? date('d/m/Y H:i', strtotime($n['published_at'])) : '—';
  $cat = trim((string)($n['category'] ?? ''));
  $tags = trim((string)($n['tags'] ?? ''));

  $parts = [$dt];
  if ($cat !== '') $parts[] = $cat;
  if ($tags !== '') $parts[] = $tags;

  return implode(' • ', $parts);
}

require __DIR__ . '/partials/header.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($config['site']['name']) ?> • Notícias</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
      <div class="panel-card panel-full">
        <div class="boss-header">
          <h3>Notícias</h3>
        </div>

        <div class="news-list">
          <?php if (!$news): ?>
            <div class="news-item">
              <div class="news-title">Sem notícias publicadas</div>
              <div class="news-meta">—</div>
            </div>
          <?php else: ?>
            <?php foreach ($news as $n): ?>
              <div class="news-item">
                <div class="news-title"><?= htmlspecialchars($n['title']) ?></div>
                <div class="news-meta"><?= htmlspecialchars(newsMetaLine($n)) ?></div>

                <?php if (!empty($n['summary'])): ?>
                  <div class="news-summary" style="opacity:.85; font-size:13px; margin-top:6px;">
                    <?= htmlspecialchars($n['summary']) ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
          <div class="boss-pager" style="margin-top:14px;">
            <div class="info">
              Página <?= $page ?> / <?= $totalPages ?> • Mostrando <?= count($news) ?> de <?= $total ?>
            </div>

            <div class="nav" style="display:flex; gap:10px;">

              <a class="boss-btn" href="?p=<?= max(1, $page-1) ?>" <?= $page <= 1 ? 'style="pointer-events:none;opacity:.45;"' : '' ?>>◀ Anterior</a>
              <a class="boss-btn" href="?p=<?= min($totalPages, $page+1) ?>" <?= $page >= $totalPages ? 'style="pointer-events:none;opacity:.45;"' : '' ?>>Próximo ▶</a>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
