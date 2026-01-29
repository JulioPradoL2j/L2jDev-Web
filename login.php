<?php
session_start();

require 'db.php';

$config = require __DIR__ . '/config.php';

$SUFFIX_CFG      = $config['suffix'] ?? [];
$SUFFIX_ENABLED  = (bool)($SUFFIX_CFG['enabled'] ?? false);
$SUFFIX_OPTIONAL = (bool)($SUFFIX_CFG['optional'] ?? true);
$SUFFIX_DEFAULT  = (bool)($SUFFIX_CFG['default_use'] ?? false);
$SUFFIX_OPTIONS  = (array)($SUFFIX_CFG['options'] ?? []);

require __DIR__ . '/partials/header.php';

if (isset($_SESSION['user'])) {
    header('Location: panel.php');
    exit;
}

$msg = '';
$ip  = $_SERVER['REMOTE_ADDR'];

/* =========================
   FIREWALL – BLOQUEIO PRÉVIO
========================= */
$stmt = $pdo->prepare("SELECT blocked_until, reason FROM site_firewall WHERE ip=?");
$stmt->execute([$ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

$blockedUntil = $row['blocked_until'] ?? null;
$reason       = $row['reason'] ?? 'blocked';

if ($blockedUntil && (int)$blockedUntil > time()) {
    // manda para página própria
    header("Location: blocked.php?until=".(int)$blockedUntil."&reason=".urlencode($reason));
    exit;
}


/* =========================
   LOGIN
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $baseUser  = trim($_POST['user'] ?? '');
    $pass      = (string)($_POST['pass'] ?? '');
    $suffixKey = (string)($_POST['suffix'] ?? '');

    $useSuffix = $SUFFIX_ENABLED && (
        $SUFFIX_OPTIONAL ? isset($_POST['use_suffix']) : true
    );

    // 1) Validação mínima do usuário base (antes de montar loginFinal)
    if ($baseUser === '' || $pass === '') {
        $msg = 'Dados inválidos.';
    }

    // 2) Monta loginFinal (e valida sufixo se necessário)
    $user = strtolower($baseUser);

    if (!$msg && $useSuffix) {
        if (!isset($SUFFIX_OPTIONS[$suffixKey])) {
            $msg = 'Selecione um sufixo válido.';
        } else {
            $user .= '@' . $SUFFIX_OPTIONS[$suffixKey];
        }
    }

    // 3) Se já deu erro (ex: sufixo inválido), NÃO continua (não roda captcha nem DB)
    if ($msg) {
        // opcional: registrar tentativa com erro de sufixo
        $pdo->prepare("
            INSERT INTO site_log (ip, account, action, details, time)
            VALUES (?, ?, 'login_fail', 'invalid_suffix', UNIX_TIMESTAMP())
        ")->execute([$ip, $user]);

        // não faz mais nada neste POST
    } else {

        /* =========================
           CAPTCHA (OBRIGATÓRIO)
        ========================= */
        $captcha     = trim($_POST['captcha'] ?? '');
        $expected    = $_SESSION['captcha_answer'] ?? null;
        $captchaTime = (int)($_SESSION['captcha_time'] ?? 0);

        $captchaOk = true;

        if (!$expected || !$captchaTime || (time() - $captchaTime) > 180) {
            $captchaOk = false;
            $msg = 'Captcha expirado. Atualize e tente novamente.';
        } elseif ($captcha === '' || !hash_equals((string)$expected, (string)$captcha)) {
            $captchaOk = false;
            $msg = 'Captcha inválido.';
        }

        unset($_SESSION['captcha_answer'], $_SESSION['captcha_time']);

        if (!$captchaOk) {
            $pdo->prepare("
                INSERT INTO site_log (ip, account, action, details, time)
                VALUES (?, ?, 'login_fail', 'invalid_captcha', UNIX_TIMESTAMP())
            ")->execute([$ip, $user]);

        } else {

            // A PARTIR DAQUI seu código de consulta ao banco pode continuar igual
            $stmt = $pdo->prepare("
                SELECT password_site, failed_logins
                FROM accounts
                WHERE login=?
            ");
            $stmt->execute([$user]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);

           if (!$acc || !password_verify($pass, $acc['password_site'])) {

				$newFails = null;

				if ($acc) {
					$newFails = ((int)$acc['failed_logins']) + 1;
			
					$pdo->prepare("
						UPDATE accounts
						SET failed_logins = ?,
							last_failed = UNIX_TIMESTAMP()
						WHERE login=?
					")->execute([$newFails, $user]);
				}
			
				$pdo->prepare("
					INSERT INTO site_log (ip, account, action, details, time)
					VALUES (?, ?, 'login_fail', 'invalid_credentials', UNIX_TIMESTAMP())
				")->execute([$ip, $user]);
			
				// brute-force → firewall (apenas quando atingir 5)
				if ($acc && $newFails >= 5) {
					$pdo->prepare("
						INSERT INTO site_firewall (ip, blocked_until, reason)
						VALUES (?, UNIX_TIMESTAMP() + 600, 'brute_force')
						ON DUPLICATE KEY UPDATE
							blocked_until = VALUES(blocked_until),
							reason = VALUES(reason)
					")->execute([$ip]);
			
					$pdo->prepare("
						INSERT INTO site_log (ip, account, action, details, time)
						VALUES (?, ?, 'firewall_block', 'brute_force', UNIX_TIMESTAMP())
					")->execute([$ip, $user]);
				}
			
				$msg = 'Usuário ou senha inválidos.';
			}

 else {

                session_regenerate_id(true);

                $pdo->prepare("
                    UPDATE accounts
                    SET failed_logins = 0,
                        last_ip = ?
                    WHERE login=?
                ")->execute([$ip, $user]);

                $pdo->prepare("
                    INSERT INTO site_log (ip, account, action, time)
                    VALUES (?, ?, 'login_success', UNIX_TIMESTAMP())
                ")->execute([$ip, $user]);


                $_SESSION['user'] = $user;
                header('Location: panel.php');
                exit;
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($config['site']['name']); ?> Login</title>
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
      <h3>Seja bem vindo ao <?= htmlspecialchars($config['site']['name']); ?></h3>
      <p>Sua diversão e nossa satisfação.</p>

      <ul class="security-list">
        <li>Respeite outros jogadores.</li>
      </ul>
	  
		<div class="panel-image">
			<img src="assets/img/DIVUL_OBT.png" alt="Divulgação Oficial">
		</div>
	  
    </div>

    <div class="panel-card">
      

<form method="post" autocomplete="off">
 <h2>Acessar Conta</h2>

   <label>Login</label>

<div class="login-row">
  <input class="login-input"
         type="text"
         name="user"
         placeholder="Usuário"
         required
         minlength="4"
         maxlength="16"
         pattern="[A-Za-z0-9_]{4,16}">
</div>

<?php if ($SUFFIX_ENABLED && $SUFFIX_OPTIONAL): ?>
  <label class="suffix-toggle" for="useSuffixLogin">
    <input type="checkbox"
           name="use_suffix"
           id="useSuffixLogin"
           <?= ($SUFFIX_DEFAULT || isset($_POST['use_suffix'])) ? 'checked' : '' ?>

    <span>Usar sufixo do login</span>
  </label>
<?php endif; ?>

<?php if ($SUFFIX_ENABLED): ?>
  <div class="suffix-row <?= (!$SUFFIX_OPTIONAL ? 'active' : '') ?>" id="suffixRowLogin">
    <select class="suffix-select" name="suffix" id="suffixSelectLogin" <?= $SUFFIX_OPTIONAL ? '' : 'required' ?>>
      <option value="" disabled selected>Escolha seu sufixo</option>

      <?php foreach ($SUFFIX_OPTIONS as $k => $v): ?>
        <option value="<?= htmlspecialchars($k) ?>" <?= ($suffixKey === $k ? 'selected' : '') ?>>
  @<?= htmlspecialchars($v) ?>
</option>

      <?php endforeach; ?>
    </select>
  </div>

 
<?php endif; ?>

    <input type="password" name="pass" placeholder="Senha" required>

    <label style="display:block;margin-top:10px;">Verificação Anti-Bot</label>

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

    <button type="submit">Entrar</button>

    <?php if ($msg): ?>
      <p style="text-align:center;margin-top:12px;color:#ff7d7d;">
        <?= htmlspecialchars($msg) ?>
      </p>
    <?php endif; ?>

  </form>
    </div>

  </div>
	
</section>
</main>
<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
