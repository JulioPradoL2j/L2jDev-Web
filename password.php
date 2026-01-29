<?php
require 'db.php';
session_start();
require __DIR__ . '/partials/header.php';

$msg = '';
$error = '';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$user = $_SESSION['user'];
$ip   = $_SERVER['REMOTE_ADDR'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* =========================
       CAPTCHA (OBRIGATÓRIO)
    ========================= */
    $captcha     = trim($_POST['captcha'] ?? '');
    $expected    = $_SESSION['captcha_answer'] ?? null;
    $captchaTime = (int)($_SESSION['captcha_time'] ?? 0);

    $captchaOk = true;

    if (!$expected || !$captchaTime || (time() - $captchaTime) > 180) {
        $captchaOk = false;
        $error = 'Captcha expirado. Atualize e tente novamente.';
    } elseif ($captcha === '' || !hash_equals((string)$expected, (string)$captcha)) {
        $captchaOk = false;
        $error = 'Captcha inválido.';
    }

    // invalida captcha após qualquer tentativa (evita reuse)
    unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);

    if (!$captchaOk) {
        $pdo->prepare("
            INSERT INTO site_log (ip, account, action, details, time)
            VALUES (?, ?, 'password_fail', 'invalid_captcha', UNIX_TIMESTAMP())
        ")->execute([$ip, $user]);

    } else {

        /* =========================
           PROCESSA TROCA
        ========================= */
        $current = (string)($_POST['current_pass'] ?? '');
        $new     = (string)($_POST['new_pass'] ?? '');
        $confirm = (string)($_POST['confirm_pass'] ?? '');

        if ($current === '' || $new === '' || $confirm === '') {
            $error = 'Preencha todos os campos.';
        } elseif ($new !== $confirm) {
            $error = 'As senhas não coincidem.';
        } elseif (strlen($new) < 6 || strlen($new) > 32) {
            $error = 'A senha deve ter entre 6 e 32 caracteres.';
        } else {

            $stmt = $pdo->prepare("SELECT password_site FROM accounts WHERE login=?");
            $stmt->execute([$user]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$acc || !password_verify($current, $acc['password_site'])) {
                $error = 'Senha atual incorreta.';

                $pdo->prepare("
                    INSERT INTO site_log (ip, account, action, details, time)
                    VALUES (?, ?, 'password_fail', 'invalid_current', UNIX_TIMESTAMP())
                ")->execute([$ip, $user]);

            } else {

                // hashes
                $hashL2J  = base64_encode(sha1($new, true));
                $hashSite = password_hash($new, PASSWORD_ARGON2ID);

                $pdo->prepare("
                    UPDATE accounts
                    SET password = ?,
                        password_site = ?
                    WHERE login = ?
                ")->execute([$hashL2J, $hashSite, $user]);

                $pdo->prepare("
                    INSERT INTO site_log (ip, account, action, details, time)
                    VALUES (?, ?, 'password', 'change', UNIX_TIMESTAMP())
                ")->execute([$ip, $user]);

                $msg = 'Senha alterada com sucesso.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($config['site']['name']); ?> Segurança da Conta</title>
<link rel="stylesheet" href="assets/css/style.css">
<script src="assets/js/app.js" defer></script>
</head>
<body>

<audio id="bgMusic" src="assets/audio/theme.mp3" autoplay loop preload="auto"></audio>
<button id="musicToggle" class="music-toggle" aria-label="Silenciar música" title="Silenciar música">🔊</button>
<!-- HERO SOMENTE IMAGEM -->
<section class="hero hero-home"></section>

<main>

<!-- BLOCO DE INFO ABAIXO DO HERO -->
<section class="hero-info">

  <div class="panel-grid">

    <div class="panel-card">
      <h3>Trocar Senha</h3>
      <p>Para sua segurança, informe sua senha atual e defina uma nova senha forte.</p>

      <ul class="security-list">
        <li>Mínimo de 6 caracteres</li>
        <li>Evite senhas já utilizadas</li>
        <li>Não compartilhe sua senha</li>
      </ul>
	  
		<div class="panel-image">
			<img src="assets/img/DIVUL_OBT.png" alt="Divulgação Oficial">
		</div>
	  
    </div>

    <div class="panel-card">
      <h3>Alteração</h3>

      <form method="post" class="panel-form" autocomplete="off">

        <label>Senha atual</label>
        <input type="password" name="current_pass" required>

        <label>Nova senha</label>
        <input type="password" name="new_pass" required>

        <label>Confirmar nova senha</label>
        <input type="password" name="confirm_pass" required>

        <label style="margin-top:10px;">Verificação Anti-Bot</label>

        <div class="captcha-row">
          <img class="captcha-img"
               src="captcha.php?<?= time() ?>"
               alt="captcha"
               onclick="this.src='captcha.php?'+Date.now()"
               title="Clique para atualizar">

          <input class="captcha-input"
                 type="text"
                 name="captcha"
                 inputmode="numeric"
                 autocomplete="off"
                 placeholder="Resposta"
                 required>
        </div>

        <small class="captcha-hint">Clique na imagem para gerar outro captcha.</small>

        <button type="submit">Salvar Alterações</button>

        <?php if ($msg): ?>
          <div class="msg success"><?= htmlspecialchars($msg) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="msg error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

      </form>
    </div>

  </div>
</section>
</main>

<footer>
  <p>Área segura da conta</p>
</footer>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
