<?php
require 'db.php';

$config = require __DIR__ . '/config.php';

$SUFFIX_CFG = $config['suffix'] ?? [];
$SUFFIX_ENABLED  = (bool)($SUFFIX_CFG['enabled'] ?? false);
$SUFFIX_OPTIONAL = (bool)($SUFFIX_CFG['optional'] ?? true);
$SUFFIX_DEFAULT  = (bool)($SUFFIX_CFG['default_use'] ?? false);
$SUFFIX_OPTIONS  = (array)($SUFFIX_CFG['options'] ?? []);

require __DIR__ . '/partials/header.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$msg = '';
$ok  = false;

function clientIp(): string {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function now(): int {
    return time();
}

/** 3 opções fixas de sufixo */
$SUFFIX_OPTIONS = [
    'BR'   => 'br',      // exemplo: usuario@br
    'MAIN' => 'main',    // usuario@main
    'VIP'  => 'vip',     // usuario@vip
];

/** CSRF */
if (empty($_SESSION['csrf_register'])) {
    $_SESSION['csrf_register'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_register'];

/** Anti-bot: tempo mínimo para enviar (em segundos) */
if (empty($_SESSION['reg_form_time'])) {
    $_SESSION['reg_form_time'] = microtime(true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

// =========================
// CAPTCHA (obrigatório)
// =========================
$captcha = trim($_POST['captcha'] ?? '');
$expected = $_SESSION['captcha_answer'] ?? null;
$captchaTime = (int)($_SESSION['captcha_time'] ?? 0);

// expira em 3 minutos
if (!$msg) {
    if (!$expected || !$captchaTime || (time() - $captchaTime) > 180) {
        $msg = 'Captcha expirado. Atualize e tente novamente.';
    } elseif (!hash_equals((string)$expected, $captcha)) {
        $msg = 'Captcha inválido.';
    }
}


    $ip = clientIp();

    // =========================
    // Anti-bot: CSRF
    // =========================
    $csrfPost = $_POST['csrf'] ?? '';
    if (!hash_equals($csrf, $csrfPost)) {
        $msg = 'Falha de validação. Recarregue a página e tente novamente.';
    }

    // =========================
    // Anti-bot: honeypot (campo deve vir vazio)
    // =========================
    $hp = trim($_POST['company'] ?? '');
    if (!$msg && $hp !== '') {
        $msg = 'Requisição inválida.';
    }

    // =========================
    // Anti-bot: tempo mínimo de preenchimento
    // =========================
    $t0 = (float)($_SESSION['reg_form_time'] ?? microtime(true));
    $elapsed = microtime(true) - $t0;
    if (!$msg && $elapsed < 2.5) { // bots costumam postar em < 1s
        $msg = 'Registro muito rápido. Tente novamente.';
    }

    // =========================
    // Entrada do usuário
    // =========================
    $baseUser = trim($_POST['user'] ?? '');
    $pass     = (string)($_POST['pass'] ?? '');
	$useSuffix = $SUFFIX_ENABLED && (
		$SUFFIX_OPTIONAL ? isset($_POST['use_suffix']) : true
	);
	
	$suffixKey = (string)($_POST['suffix'] ?? '');



    // =========================
    // Validar sufixo
    // =========================
	if (!$msg && $useSuffix) {
		if (!isset($SUFFIX_OPTIONS[$suffixKey])) {
			$msg = 'Selecione um sufixo válido.';
		}
	}


    // =========================
    // Validar usuário base (sem sufixo)
    // - 4 a 16
    // - letras/números/underscore
    // =========================
    if (!$msg && !preg_match('/^[a-zA-Z0-9_]{4,16}$/', $baseUser)) {
        $msg = 'Usuário inválido (use 4-16 caracteres: letras, números e _).';
    }

    // =========================
    // Validar senha
    // =========================
    if (!$msg && (strlen($pass) < 6 || strlen($pass) > 32)) {
        $msg = 'Senha inválida (6 a 32 caracteres).';
    }

    // Monta login final com sufixo
	if ($useSuffix) {
		$suffix = $SUFFIX_OPTIONS[$suffixKey];
		$loginFinal = strtolower($baseUser) . '@' . $suffix;
	} else {
		$suffix = null;
		$loginFinal = strtolower($baseUser);
	}


    // =========================
    // Rate limit por IP (registros/hora)
    // =========================
    if (!$msg) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM site_log
            WHERE ip = ?
              AND action IN ('register_success','register_fail')
              AND time > UNIX_TIMESTAMP() - 3600
        ");
        $stmt->execute([$ip]);

        if ((int)$stmt->fetchColumn() >= 5) {
            $msg = 'Limite de registros atingido. Tente novamente mais tarde.';
        }
    }

    // =========================
    // Criar conta
    // =========================
    if (!$msg) {
        // Hash L2J (Interlude): base64(sha1(pass, true))
        $hashL2J  = base64_encode(sha1($pass, true));
        // Hash do site
        $hashSite = password_hash($pass, PASSWORD_ARGON2ID);

        try {
            $pdo->beginTransaction();

            // Verifica se já existe
            $chk = $pdo->prepare("SELECT 1 FROM accounts WHERE login = ? LIMIT 1");
            $chk->execute([$loginFinal]);
            if ($chk->fetchColumn()) {
                throw new RuntimeException('Conta já existe.');
            }

            $ins = $pdo->prepare("
                INSERT INTO accounts
                    (login, password, password_site, access_level, lastactive, last_ip, failed_logins, last_failed, balance)
                VALUES
                    (?, ?, ?, 0, ?, ?, 0, 0, 0)
            ");
 

            $ins->execute([
                $loginFinal,
                $hashL2J,
                $hashSite,
                now(),
                $ip
            ]);

            $log = $pdo->prepare("
                INSERT INTO site_log
                    (ip, account, action, details, time)
                VALUES
                    (?, ?, 'register_success', ?, UNIX_TIMESTAMP())
            ");
            $log->execute([
                $ip,
                $loginFinal,
				$useSuffix ? ('suffix=' . $suffix) : 'suffix=none'

            ]);

            $pdo->commit();

            $ok = true;
            $msg = 'Conta criada com sucesso: ' . htmlspecialchars($loginFinal);
			unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);

            // Rotaciona token/tempo do form
            $_SESSION['csrf_register'] = bin2hex(random_bytes(16));
            $_SESSION['reg_form_time'] = microtime(true);

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Log de falha
            $log = $pdo->prepare("
                INSERT INTO site_log
                    (ip, account, action, details, time)
                VALUES
                    (?, ?, 'register_fail', ?, UNIX_TIMESTAMP())
            ");
            $log->execute([
                $ip,
                $loginFinal ?? '-',
                'error=' . substr($e->getMessage(), 0, 180)
            ]);

            $msg = ($e instanceof RuntimeException) ? $e->getMessage() : 'Erro ao criar conta.';
        }
    }
}

// sempre reinicia o timer de preenchimento ao carregar a página
$_SESSION['reg_form_time'] = microtime(true);

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($config['site']['name']); ?> Registro</title>
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
      <h3>Atenção</h3>
      <p>Para sua segurança, defina uma senha forte.</p>

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
      
		<form method="post" autocomplete="off">
			 <h2>Criar Conta</h2>
		
			<input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_register']) ?>">
		
			<!-- Honeypot -->
			<div style="position:absolute;left:-9999px;top:-9999px;">
			<input type="text" name="company" tabindex="-1">
			</div>
		
			<label>Login</label>
		
			<div class="login-row">
			<input class="login-input" type="text" name="user" placeholder="Usuário" required minlength="4" maxlength="16" pattern="[A-Za-z0-9_]{4,16}">
			</div>
		
			<!-- CHECKBOX -->
			<?php if ($SUFFIX_ENABLED && $SUFFIX_OPTIONAL): ?>
			<label class="suffix-toggle" for="useSuffix">
				<input type="checkbox"
					name="use_suffix"
					id="useSuffix"
					<?= $SUFFIX_DEFAULT ? 'checked' : '' ?>>
				<span>Usar sufixo no login</span>
			</label>
			<?php endif; ?>
		
		
			<!-- SELECT (inativo por padrão) -->
			<?php if ($SUFFIX_ENABLED): ?>
		<div class="suffix-row <?= (!$SUFFIX_OPTIONAL ? 'active' : '') ?>" id="suffixRow">
		
			<select class="suffix-select" name="suffix" id="suffixSelect"
					<?= $SUFFIX_OPTIONAL ? '' : 'required' ?>>
			<option value="" disabled selected>Escolha seu sufixo</option>
		
			<?php foreach ($SUFFIX_OPTIONS as $k => $v): ?>
				<option value="<?= htmlspecialchars($k) ?>">
				@<?= htmlspecialchars($v) ?>
				</option>
			<?php endforeach; ?>
			</select>
		</div>
		<?php endif; ?>
		
		
		
			<input type="password" name="pass" placeholder="Senha" required minlength="6" maxlength="32">
		
			<label>Verificação Anti-Bot</label>
		
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
		
			<button type="submit">Registrar</button>
		
			<?php if ($msg): ?>
			<p style="text-align:center;color:<?= $ok ? '#7fd68a' : '#ff8e7e' ?>;">
				<?= htmlspecialchars($msg) ?>
			</p>
			<?php endif; ?>
		
		<p class="login-hint <?= (!$SUFFIX_OPTIONAL ? 'active' : '') ?>" id="loginHint">
		Dica: o login final será <strong>usuario@sufixo</strong>.
		</p>
		
		
		</form>
    </div>

  </div>
	
</section>
</main>



<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
