<?php
$config = $config ?? (file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : ['site'=>['name'=>'Lineage II Server']]);
$title  = $config['site']['name'] ?? 'Lineage II Server';
require 'db.php';
require __DIR__ . '/partials/header.php';
date_default_timezone_set('America/Sao_Paulo');

$stmt = $pdo->query("
    SELECT server_type, status, last_heartbeat
    FROM server_status
");

$serverStatus = [
    'LOGIN' => ['status' => 'OFFLINE', 'last_heartbeat' => null],
    'GAME'  => ['status' => 'OFFLINE', 'last_heartbeat' => null]
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $type = strtoupper(trim($row['server_type'] ?? ''));
    if ($type === 'LOGIN' || $type === 'GAME') {
        $serverStatus[$type] = $row;
    }
}

$HEARTBEAT_LIMIT = 90;

function isServerOnline(array $row, int $limit): bool
{
    $status = strtoupper(trim($row['status'] ?? 'OFFLINE'));
    if ($status !== 'ONLINE')
        return false;

    $hbRaw = $row['last_heartbeat'] ?? null;
    if (!$hbRaw)
        return false;

    $hb = strtotime($hbRaw);
    if ($hb === false)
        return false;

    return (time() - $hb) <= $limit;
}

$loginName = $config['servers']['login']['name'] ?? 'Login Server';
$gameName  = $config['servers']['game']['name'] ?? 'Game Server';

$isLoginOnline = isServerOnline($serverStatus['LOGIN'], $HEARTBEAT_LIMIT);
$isGameOnline  = isServerOnline($serverStatus['GAME'],  $HEARTBEAT_LIMIT);

$loginClass = $isLoginOnline ? 'online' : 'offline';
$gameClass  = $isGameOnline  ? 'online' : 'offline';

$loginText  = $isLoginOnline ? 'Online' : 'Offline';
$gameText   = $isGameOnline  ? 'Online' : 'Offline';

/* =========================
   MÉTRICAS DO SERVIDOR
========================= */
$totalAccounts = (int)$pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$totalChars    = (int)$pdo->query("SELECT COUNT(*) FROM characters")->fetchColumn();

$newsHome = $pdo->query("
  SELECT id, title, category, tags, published_at
  FROM site_news
  WHERE is_published = 1
  ORDER BY is_pinned DESC, published_at DESC, id DESC
  LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

function newsMetaLine(array $n): string {
  $dt = $n['published_at'] ? date('d/m/Y', strtotime($n['published_at'])) : '—';
  $cat = trim((string)($n['category'] ?? ''));
  $tags = trim((string)($n['tags'] ?? ''));

  $parts = [$dt];
  if ($cat !== '') $parts[] = $cat;
  if ($tags !== '') $parts[] = $tags;

  return implode(' • ', $parts);
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">

  <title><?= htmlspecialchars($title) ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- SEO básico -->
  <meta name="description" content="Servidor Lineage II Interlude (L2J) com foco em estabilidade, performance e experiência clássica. Eventos, ranking e comunidade ativa.">
  <meta name="keywords" content="Lineage 2, Lineage II, L2J, Interlude, MMORPG, server, private server, PvP, PvE, clan, siege, olympiad">
  <meta name="author" content="<?= htmlspecialchars($config['site']['owner'] ?? $title) ?>">

  <!-- Social preview (opcional) -->
  <meta property="og:title" content="<?= htmlspecialchars($title) ?>">
  <meta property="og:description" content="Lineage II Interlude (L2J) — servidor clássico, estável e com conteúdo.">
  <meta property="og:type" content="website">
  <meta property="og:locale" content="pt_BR">
  <meta property="og:image" content="assets/img/DIVUL_OBT.png">

  <!-- ÍCONE / FAVICON -->
	<link rel="icon" href="/favicon.ico">
	<link rel="apple-touch-icon" href="/favicon.ico">
  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/style.css">


  <!-- JS -->
  <script src="assets/js/app.js" defer></script>
</head>
<body>

<!-- Música -->
<audio id="bgMusic" src="assets/audio/theme.mp3" autoplay loop preload="auto"></audio>
<button id="musicToggle" class="music-toggle" aria-label="Silenciar música" title="Silenciar música">🔊</button>
<!-- HERO SOMENTE IMAGEM -->
<section class="hero hero-home"></section>

<main>


<!-- BLOCO DE INFO ABAIXO DO HERO -->
<section class="hero-info">
  <div class="hero-info-inner">


    <div class="hero-mini">
      <div class="hero-mini-item">
        <span class="k">Level UP</span>
        <span class="v">XP x<?= (int)($config['rates']['xp'] ?? 10) ?> • SP x<?= (int)($config['rates']['sp'] ?? 10) ?></span>
      </div>
      <div class="hero-mini-item">
        <span class="k">Rates Enchant</span>
        <span class="v">Min +<?= (int)($config['rates']['enchant_min'] ?? 10) ?> • Max +<?= (int)($config['rates']['enchant_max'] ?? 10) ?></span>
      </div>
      <div class="hero-mini-item">
        <span class="k">Rates Farm</span>
        <span class="v">Adena x<?= (int)($config['rates']['adena'] ?? 10) ?> • Drop x<?= (int)($config['rates']['drop'] ?? 10) ?></span>
      </div>
 
      <div class="hero-mini-item">
        <span class="k">PvP / PvE</span>
        <span class="v">Frenético</span>
      </div>
      <div class="hero-mini-item">
        <span class="k">Balance</span>
        <span class="v">Skills • Classes </span>
      </div>
      <div class="hero-mini-item">
        <span class="k">Hwid</span>
        <span class="v">Protection • Filles</span>
      </div>
      <div class="hero-mini-item">
        <span class="k">Crônica</span>
        <span class="v">Interlude</span>
      </div>
    </div>

  </div>
</section>


  <!-- INFO GRID (o que um site L2 precisa) -->
  <section class="home-grid">

    <!-- SERVIDOR / FEATURES -->
    <div class="panel-card">
      <h3>Recursos do Servidor</h3>
      <ul class="feature-list">
        <li>Interlude (L2J) com otimizações de performance e correções.</li>
        <li>Eventos automáticos: TvT, CTF, DeathMatch.</li>
        <li>Proteções: anti-bot, anti-inject.</li>
        <li>Sistema de VIP/AIO com regras claras e prazo visível.</li>
        <li>Economia controlada, farm justo e progressão consistente.</li>
      </ul>
    </div>

    <!-- STATUS / UPTIME (pode ser integrado ao seu port-check do painel) -->
    <div class="panel-card">
      <h3>Status do Servidor</h3>

      <div class="status-box">
<div class="status-row">
  <span class="label"><?= htmlspecialchars($loginName) ?></span>
  <span class="value status <?= $loginClass ?>"><?= $loginText ?></span>
</div>

<div class="status-row">
  <span class="label"><?= htmlspecialchars($gameName) ?></span>
  <span class="value status <?= $gameClass ?>"><?= $gameText ?></span>
</div>
      </div>
        <div class="mini-metrics">
            <div class="mini-metric">
                <span class="label">Contas criadas</span>
                <span class="value"><?= number_format($totalAccounts, 0, ',', '.') ?></span>
            </div>
            <div class="mini-metric">
                <span class="label">Personagens</span>
                <span class="value"><?= number_format($totalChars, 0, ',', '.') ?></span>
            </div>
        </div>
 
    </div>

    <!-- DOWNLOAD -->
    <div class="panel-card">
      <h3>Download e Instalação</h3>
      <ol class="steps">
        <li>Baixe o cliente Interlude recomendado (ou seu patch completo).</li>
        <li>Extraia na pasta do jogo e aplique o patch.</li>
        <li>Abra o launcher / l2.exe e faça login.</li>
      </ol>
      <div class="panel-actions">
        <a class="btn primary" href="download.php">Abrir Download</a>
      </div>
       
    </div>

    <!-- REGRAS -->
    <div class="panel-card">
      <h3>Regras e Fair Play</h3>
      <ul class="feature-list">
        <li>Proibido bot / macro / automação não autorizada.</li>
        <li>Proibido abuso de bugs e exploração de falhas.</li>
        <li>Respeito no chat (sem racismo, doxxing, ameaças).</li>
        <li>Trades: use sistemas oficiais para evitar golpes.</li>
      </ul>
      <div class="panel-actions">
        <a class="btn" href="rules.php">Ler regras completas</a>
      </div>
    </div>

    <!-- RANKINGS -->
    <div class="panel-card">
      <h3>Rankings</h3>
      <li>Top PvP, PK, Clans, Boss status.</li>

      <div class="panel-actions">
        <a class="btn" href="ranking.php">Ver ranking</a>
        <a class="btn" href="siege.php">Sieges</a>
      </div>
    </div>

    <!-- PATCH NOTES / NOTÍCIAS -->
	<div class="panel-card">
	<h3>Atualizações</h3>
	
	<div class="news-list">
		<?php if (!$newsHome): ?>
		<div class="news-item">
			<div class="news-title">Sem notícias por enquanto</div>
			<div class="news-meta">—</div>
		</div>
		<?php else: ?>
		<?php foreach ($newsHome as $n): ?>
			<div class="news-item">
			<div class="news-title"><?= htmlspecialchars($n['title']) ?></div>
			<div class="news-meta"><?= htmlspecialchars(newsMetaLine($n)) ?></div>
			</div>
		<?php endforeach; ?>
		<?php endif; ?>
	</div>
	
	<div class="panel-actions">
		<a class="btn" href="news.php">Ver todas</a>
	</div>
	</div>


  </section>

  <!-- COMUNIDADE / SUPORTE -->
  <section class="community">
    <div class="panel-card panel-full">
      <h3>Comunidade e Suporte</h3>
      <p class="muted">
        Entre no Discord, tire dúvidas, reporte bugs e acompanhe anúncios oficiais.
      </p>


		<div class="community-actions community-actions--mmorpg">
		<a class="soc-btn soc-discord" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($config['site']['discord'] ?? '#') ?>">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<g clip-path="url(#clip0_42_2)">
					<path d="M20.3001 4.51C18.7216 3.79651 17.058 3.28901 15.3501 3C15.1154 3.41473 14.9038 3.84204 14.7161 4.28C12.8961 4.00844 11.046 4.00844 9.22605 4.28C9.03738 3.844 8.82605 3.41733 8.59205 3C6.88205 3.289 5.21205 3.797 3.63205 4.51C0.502052 9.1 -0.347948 13.57 0.0720517 18.01C1.91205 19.35 3.96205 20.37 6.14205 21.03C6.6354 20.376 7.07041 19.68 7.44205 18.95C6.73205 18.688 6.04205 18.364 5.39205 17.982C5.56405 17.858 5.73138 17.7333 5.89405 17.608C7.79574 18.4912 9.86726 18.9488 11.9641 18.9488C14.0608 18.9488 16.1324 18.4912 18.0341 17.608C18.1987 17.742 18.3661 17.8667 18.5361 17.982C17.8823 18.3644 17.1963 18.689 16.4861 18.952C16.8591 19.678 17.2941 20.372 17.7861 21.022C19.9761 20.37 22.0261 19.352 23.8661 18.012C24.3641 12.902 23.0151 8.462 20.2961 4.512L20.3001 4.51ZM8.00005 15.31C6.82005 15.31 5.84005 14.25 5.84005 12.94C5.84005 11.63 6.78405 10.56 8.00005 10.56C9.21005 10.56 10.1801 11.63 10.1601 12.94C10.1401 14.25 9.20705 15.31 8.00005 15.31ZM15.9701 15.31C14.7801 15.31 13.8101 14.25 13.8101 12.94C13.8101 11.63 14.7541 10.56 15.9701 10.56C17.1901 10.56 18.1501 11.63 18.1301 12.94C18.1101 14.25 17.1791 15.31 15.9701 15.31Z" fill="currentColor"></path>
				</g>
				<defs><clipPath id="clip0_42_2"><rect width="24" height="24" fill="white"></rect></clipPath></defs>
				</svg>
			<span>Discord</span>
		</a>
		
		<a class="soc-btn soc-whatsapp" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($config['site']['whatsapp_link'] ?? '#') ?>">
			<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
					xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M20.5 11.9c0 4.7-3.8 8.6-8.6 8.6-1.5 0-2.9-.4-4.1-1l-3.3 1 1.1-3.2c-.7-1.3-1.1-2.7-1.1-4.3C4.5 7.2 8.4 3.4 13.1 3.4c4.7 0 8.4 3.8 8.4 8.5Z" stroke="currentColor" stroke-width="1.6"/>
				<path d="M10.2 8.6c.2-.4.3-.4.6-.4h.5c.1 0 .3 0 .4.3l.7 1.6c.1.2.1.4 0 .6l-.3.4c-.1.2-.2.3 0 .6.2.3.7 1.2 1.5 1.9.9.8 1.6 1 1.9 1.1.2 0 .4 0 .5-.2l.6-.7c.2-.2.3-.2.6-.1l1.6.8c.3.1.3.3.3.4 0 .2-.1.9-.6 1.3-.5.5-1.2.7-2 .5-.8-.2-2.6-1-3.8-2.1-1.4-1.2-2.3-2.8-2.6-3.6-.3-.8 0-1.5.2-1.8Z" fill="currentColor"/>
				</svg>
			<span>WhatsApp</span>
		</a>
		
		<a class="soc-btn soc-facebook" target="_blank" rel="noopener noreferrer" href="<?= htmlspecialchars($config['site']['facebook'] ?? '#') ?>">
		<svg width="24" height="24" viewBox="0 0 24 24" fill="none"
					xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
				<path d="M22 12C22 6.48 17.52 2 12 2C6.48 2 2 6.48 2 12C2 16.84 5.44 20.87 10 21.8V15H8V12H10V9.5C10 7.57 11.57 6 13.5 6H16V9H14C13.45 9 13 9.45 13 10V12H16V15H13V21.95C18.05 21.45 22 17.19 22 12Z" fill="currentColor"></path>
				</svg>
			<span>Facebook</span>
		</a>
		
		<a class="soc-btn soc-support is-primary" href="support.php">
			<span>Central de Suporte</span>
		</a>
		</div>
    </div>
	
	
</section>

</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>


</body>
</html>
