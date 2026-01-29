<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$config = $config ?? (file_exists(__DIR__ . '/../config.php') ? require __DIR__ . '/../config.php' : ['site'=>['name'=>'Site']]);

$isLogged = isset($_SESSION['user']);
$currentUser = $isLogged ? $_SESSION['user'] : null;

$title = $title ?? $config['site']['name'];
?>
<header>

 

    <nav>
        <a href="index.php">Começar</a>

        <?php if ($isLogged): ?>
			<a href="download.php">Baixar</a>
			<a href="news.php">Notícias</a>
			<a href="rules.php">Regras</a>
            <a href="panel.php">Painel</a>
			<a href="ranking.php">Classificação</a>
			<a href="siege.php">Castelos</a>
            <a href="boss.php">Bosses</a>
            <a href="logout.php">Sair</a>
        <?php else: ?>
			<a href="download.php">Baixar</a>
			<a href="news.php">Notícias</a>
			<a href="rules.php">Regras</a>
            <a href="register.php">Registrar</a>
            <a href="login.php">Conecte-se</a>
			<a href="ranking.php">Classificação</a>
			<a href="siege.php">Castelos</a>
			<a href="boss.php">Bosses</a>
        <?php endif; ?>
    </nav>

    <?php if ($isLogged): ?>
        <div class="nav-badge">
            Logado como <strong><?= htmlspecialchars($currentUser) ?></strong>
        </div>
    <?php endif; ?>
</header>
