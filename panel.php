<?php
// ===== DEBUG (remova em produção) =====
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ===== bootstrap =====
$config = require 'config.php';
require 'db.php';
session_start();
date_default_timezone_set('America/Sao_Paulo');
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

// Só depois do redirect check
require __DIR__ . '/partials/header.php';



/**
 * Baseado no seu ClassId.java (id = ordinal()).
 * "Mage" aqui = qualquer ClassType.MYSTIC ou ClassType.PRIEST.
 *
 * Importante:
 * - Isso não depende de "lista mínima".
 * - Se você alterar/editar o enum (ordem/novas classes), atualize esta lista.
 */
function classIsMage(int $classId): bool
{
    static $MAGE_IDS = [
        // =========================
        // HUMAN (Mystic/Priest)
        // =========================
        10, // HUMAN_MYSTIC
        11, // HUMAN_WIZARD
        12, // SORCERER
        13, // NECROMANCER
        14, // WARLOCK
        15, // CLERIC (PRIEST)
        16, // BISHOP (PRIEST)
        17, // PROPHET (PRIEST)

        // =========================
        // ELF (Mystic/Priest)
        // =========================
        25, // ELVEN_MYSTIC
        26, // ELVEN_WIZARD
        27, // SPELLSINGER
        28, // ELEMENTAL_SUMMONER
        29, // ELVEN_ORACLE (PRIEST)
        30, // ELVEN_ELDER (PRIEST)

        // =========================
        // DARK ELF (Mystic/Priest)
        // =========================
        38, // DARK_MYSTIC
        39, // DARK_WIZARD
        40, // SPELLHOWLER
        41, // PHANTOM_SUMMONER
        42, // SHILLIEN_ORACLE (PRIEST)
        43, // SHILLIEN_ELDER (PRIEST)

        // =========================
        // ORC (Mystic)
        // =========================
        60, // ORC_MYSTIC
        61, // ORC_SHAMAN
        62, // OVERLORD
        63, // WARCRYER

        // =========================
        // 3rd classes (Mystic/Priest)
        // =========================
        105, // ARCHMAGE
        106, // SOULTAKER
        107, // ARCANA_LORD
        108, // CARDINAL
        109, // HIEROPHANT

        114, // MYSTIC_MUSE
        115, // ELEMENTAL_MASTER
        116, // EVAS_SAINT

        121, // STORM_SCREAMER
        122, // SPECTRAL_MASTER
        123, // SHILLIEN_SAINT

        126, // DOMINATOR
        127, // DOOMCRYER
    ];

    return in_array($classId, $MAGE_IDS, true);
}


function getRaceKey(int $raceId): string
{
    // 0 Human, 1 Elf, 2 DarkElf, 3 Orc, 4 Dwarf
    $raceKey = 'human';
    if ($raceId === 1) $raceKey = 'elf';
    elseif ($raceId === 2) $raceKey = 'dark';
    elseif ($raceId === 3) $raceKey = 'orc';
    elseif ($raceId === 4) $raceKey = 'dwarf';
    return $raceKey;
}
function accessLevelLabel(int $level): string
{
	if ($level === -1) return 'Banned';
    if ($level === 1) return 'GM / Admin';
    if ($level === 0) return 'Member';
    return 'Staff (' . $level . ')';
}


function getSexKey(int $sex): string
{
    // 0 male, 1 female
    return ($sex === 1) ? 'female' : 'male';
}

function resolveRaceIcon(int $raceId, int $sex, int $classId): string
{
    $raceKey = getRaceKey($raceId);       // human/elf/dark/orc/dwarf
    $sexKey  = getSexKey($sex);           // male/female
    $typeKey = classIsMage($classId) ? 'mage' : 'fighter';

    // Apenas essas raças têm ícones separados por type, conforme sua pasta
    $useType = in_array($raceKey, ['human', 'orc'], true);

    $baseDir = __DIR__ . '/assets/img/races/';
    $baseUrl = 'assets/img/races/';
    $exts    = ['jpg', 'png', 'webp'];

    // 1) tenta com type (human/orc)
    if ($useType) {
        foreach ($exts as $ext) {
            $file = "{$raceKey}_{$sexKey}_{$typeKey}.{$ext}";
            if (is_file($baseDir . $file)) {
                return $baseUrl . $file;
            }
        }
    }

    // 2) tenta sem type (elf/dark/dwarf e fallback geral)
    foreach ($exts as $ext) {
        $file = "{$raceKey}_{$sexKey}.{$ext}";
        if (is_file($baseDir . $file)) {
            return $baseUrl . $file;
        }
    }

    // 3) fallback
    foreach ($exts as $ext) {
        $file = "unknow.{$ext}";
        if (is_file($baseDir . $file)) {
            return $baseUrl . $file;
        }
    }

    // último fallback (caso não exista nem unknow.*)
    return $baseUrl . 'unknow.jpg';
}

$HEARTBEAT_LIMIT = 90; // segundos




function isServerOnline(array $row, int $limit): bool
{
    if (($row['status'] ?? 'OFFLINE') !== 'ONLINE')
        return false;

    if (empty($row['last_heartbeat']))
        return false;

    return (time() - strtotime($row['last_heartbeat'])) <= $limit;
}

/* =========================
   SERVER STATUS (DATABASE)
========================= */
$stmt = $pdo->query("
    SELECT server_type, status, last_heartbeat
    FROM server_status
");

$serverStatus = [
    'LOGIN' => ['status' => 'OFFLINE', 'last_heartbeat' => null],
    'GAME'  => ['status' => 'OFFLINE', 'last_heartbeat' => null]
];

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $serverStatus[$row['server_type']] = $row;
}


/* =========================
   SERVER STATUS (PORT CHECK)
========================= */
$checkHost   = $config['servers']['check_host'] ?? '127.0.0.1';
$displayHost = $config['servers']['display_host'] ?? $checkHost;
$timeout     = (float)($config['servers']['timeout'] ?? 0.6);

$loginName = $config['servers']['login']['name'] ?? 'Login Server';
$gameName  = $config['servers']['game']['name'] ?? 'Game Server';

$isLoginOnline = isServerOnline($serverStatus['LOGIN'], $HEARTBEAT_LIMIT);
$isGameOnline  = isServerOnline($serverStatus['GAME'],  $HEARTBEAT_LIMIT);

$loginClass = $isLoginOnline ? 'online' : 'offline';
$gameClass  = $isGameOnline  ? 'online' : 'offline';

$loginText  = $isLoginOnline ? 'Online' : 'Offline';
$gameText   = $isGameOnline ? 'Online' : 'Offline';



$user = $_SESSION['user'];
$ip   = $_SERVER['REMOTE_ADDR'];



/* =========================
   INFO DA CONTA
========================= */
$stmt = $pdo->prepare("
    SELECT access_level, lastactive, last_ip, failed_logins, balance
    FROM accounts
    WHERE login=?
");
$stmt->execute([$user]);
$acc = $stmt->fetch(PDO::FETCH_ASSOC) ?: [
    'access_level' => 0,
    'lastactive' => time(),
    'last_ip' => '-',
    'failed_logins' => 0,
	'balance' => 0
];

/* =========================
   PERSONAGENS DA CONTA (até 7)
========================= */
$maxChars = 7;

$charsStmt = $pdo->prepare("
    SELECT
        obj_Id,
        char_slot,
        char_name,
        level,
        race,
        sex,
        classid,
        base_class,
        pvpkills,
        pkkills,
        karma,
        title,
        clanid,
        online,
        onlinetime,
        lastAccess,
        nobless,
        hero,
        vip,
        vip_end,
        aio,
        aio_end
    FROM characters
    WHERE account_name = ?
      AND (deletetime IS NULL OR deletetime = 0)
    ORDER BY char_slot ASC, lastAccess DESC
    LIMIT {$maxChars}
");
$charsStmt->execute([$user]);
$characters = $charsStmt->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   ÚLTIMAS AÇÕES
========================= */
$logs = $pdo->prepare("
    SELECT action, details, time
    FROM site_log
    WHERE account=?
    ORDER BY time DESC
    LIMIT 5
");
$logs->execute([$user]);
$history = $logs->fetchAll(PDO::FETCH_ASSOC);

/* =========================
   MÉTRICAS DO SERVIDOR
========================= */
$totalAccounts = (int)$pdo->query("SELECT COUNT(*) FROM accounts")->fetchColumn();
$totalChars    = (int)$pdo->query("SELECT COUNT(*) FROM characters")->fetchColumn();

/* =========================
   TOP PVP / PK
========================= */
$limitTop = 1; // ajuste aqui: 3, 5, 10...

$topPvp = $pdo->prepare("
    SELECT char_name, level, pvpkills
    FROM characters
    WHERE pvpkills IS NOT NULL
    ORDER BY pvpkills DESC, level DESC, char_name ASC
    LIMIT {$limitTop}
");
$topPvp->execute();
$topPvpList = $topPvp->fetchAll(PDO::FETCH_ASSOC);

$topPk = $pdo->prepare("
    SELECT char_name, level, pkkills
    FROM characters
    WHERE pkkills IS NOT NULL
    ORDER BY pkkills DESC, level DESC, char_name ASC
    LIMIT {$limitTop}
");
$topPk->execute();
$topPkList = $topPk->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<title><?= htmlspecialchars($config['site']['name']); ?> Painel do Usuário</title>
<link rel="stylesheet" href="assets/css/style.css">

<script src="assets/js/app.js" defer></script>
</head>
<body>
<audio id="bgMusic" src="assets/audio/theme.mp3" autoplay loop preload="auto"></audio>
<button id="musicToggle" class="music-toggle" aria-label="Silenciar música" title="Silenciar música">🔊</button>
<section class="hero hero-home"></section>
<main>

<section class="hero-info">

<!-- =========================
         PERSONAGENS
    ========================= -->
    <div class="panel-card panel-full">
        <h3>Personagens da Conta</h3>

        <?php if (empty($characters)): ?>
            <p style="color:#cfd6ff;">Nenhum personagem encontrado nesta conta.</p>
        <?php else: ?>
            <div class="char-grid">
                <?php foreach ($characters as $c): ?>
                    <?php
                        $raceId  = (int)($c['race'] ?? 0);
                        $sex     = (int)($c['sex'] ?? 0);
                        $classId = (int)($c['classid'] ?? 0);

                        $raceKey = getRaceKey($raceId);
                        $sexKey  = getSexKey($sex);
                        $typeKey = classIsMage($classId) ? 'mage' : 'fighter';

                       $iconPath = resolveRaceIcon($raceId, $sex, $classId);


                        $charId = (int)$c['obj_Id'];
                        $slot   = (int)($c['char_slot'] ?? 0);

                        $name   = $c['char_name'] ?? '';
                        $level  = (int)($c['level'] ?? 0);
                        $pvp    = (int)($c['pvpkills'] ?? 0);
                        $pk     = (int)($c['pkkills'] ?? 0);
                        $online = (int)($c['online'] ?? 0) === 1;
                    ?>
                    <button class="char-tile" type="button" data-char="<?= $charId ?>">
                        <img class="char-icon"
                             src="<?= htmlspecialchars($iconPath) ?>"
                             onerror="this.src='assets/img/races/unknow.jpg';"
                             alt="avatar" width="64" height="64">
                        <div class="char-meta">
                            <div class="char-name">
                                <?= htmlspecialchars($name) ?>
                                <span class="dot <?= $online ? 'on' : 'off' ?>"></span>
                            </div>
                            <div class="char-sub">
                                • Lv <?= $level ?> • PvP <?= $pvp ?> • PK <?= $pk ?>
                            </div>
                        </div>
                    </button>
                <?php endforeach; ?>
            </div>

            <!-- MODAL -->
            <div class="char-modal" id="charModal" aria-hidden="true">
                <div class="char-modal-backdrop" data-close="1"></div>
                <div class="char-modal-card">
                    <div class="char-modal-head">
                        <h4 id="charModalTitle">Detalhes do Personagem</h4>
                        <button class="char-close" type="button" data-close="1">X</button>
                    </div>
                    <div class="char-modal-body" id="charModalBody"></div>
                </div>
            </div>

            <!-- DADOS (JSON) -->
            <script>
                window.__chars = <?= json_encode($characters, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
            </script>
        <?php endif; ?>
    </div>
</section>
<div class="panel-grid">

    <!-- =========================
         CARD CONTA
    ========================= -->
    <div class="panel-card">
        <h3>Conta</h3>
        <p><strong>Login:</strong> <?= htmlspecialchars($user) ?></p>
        <p><strong>IP atual:</strong> <?= htmlspecialchars($ip) ?></p>
        <p><strong>Último IP:</strong> <?= htmlspecialchars($acc['last_ip'] ?? '-') ?></p>
        <?php $level = (int)($acc['access_level'] ?? 0); ?>
		<p><strong>Nível:</strong> <?= htmlspecialchars(accessLevelLabel($level)) ?></p>

		<p><strong>Balance:</strong> <?= number_format((int)($acc['balance'] ?? 0), 0, ',', '.') ?> Coins</p>

    </div>

    <!-- =========================
         SEGURANÇA
    ========================= -->
    <div class="panel-card">
        <h3>Segurança</h3>
        <p><strong>Falhas recentes:</strong> <?= (int)($acc['failed_logins'] ?? 0) ?></p>
        <p><strong>Última atividade:</strong>
            <?= date('d/m/Y H:i', (int)($acc['lastactive'] ?? time())) ?>
        </p>

        <div class="panel-actions">
            <a href="password.php" class="btn">Trocar Senha</a>
        </div>
    </div>

    <!-- =========================
         STATUS DO SERVIDOR
    ========================= -->
    <div class="panel-card">
        <h3>Status do Servidor</h3>

        <p class="status <?= $loginClass ?>"><?= htmlspecialchars($loginName) ?> <?= $loginText ?></p>

		<p class="status <?= $gameClass ?>"><?= htmlspecialchars($gameName) ?> <?= $gameText ?></p>


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

    <!-- =========================
         RANKING (UNIFICADO)
    ========================= -->
    <div class="panel-card">
        <h3>Ranking do Servidor</h3>

        <table class="rank-table rank-unified">
            <thead>
            <tr>
                <th>Type</th>
                <th>Rank</th>
                <th>Personagem</th>
                <th>Lv</th>
                <th>Point</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($topPvpList as $i => $row): ?>
                <tr>
                    <td>PvP</td>
                    <td>🥇</td>
                    <td><?= htmlspecialchars($row['char_name']) ?></td>
                    <td><?= (int)$row['level'] ?></td>
                    <td class="pvp"><?= (int)$row['pvpkills'] ?></td>
                </tr>
            <?php endforeach; ?>

            <?php foreach ($topPkList as $i => $row): ?>
                <tr>
                    <td>PK</td>
                    <td>🥇</td>
                    <td><?= htmlspecialchars($row['char_name']) ?></td>
                    <td><?= (int)$row['level'] ?></td>
                    <td class="pk"><?= (int)$row['pkkills'] ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    

    <!-- =========================
         HISTÓRICO
    ========================= -->
    <div class="panel-card panel-full">
        <h3>Últimas Atividades</h3>

        <table class="panel-table">
            <thead>
            <tr>
                <th>Ação</th>
                <th>Detalhes</th>
                <th>Data</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($history as $log): ?>
                <tr>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['details'] ?? '-') ?></td>
                    <td><?= date('d/m/Y H:i', (int)$log['time']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>
</main>

<footer>
    <p>Área restrita do usuário</p>
</footer>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
