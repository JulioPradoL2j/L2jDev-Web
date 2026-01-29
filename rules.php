<?php
// rules.php
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}
$config = $config ?? (file_exists(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : ['site'=>['name'=>'Lineage II Server']]);
$title  = $config['site']['name'] ?? 'Lineage II Server';
$discord  = $config['site']['discord'] ?? 'https://discord.gg/eUth6mTQzR';

require __DIR__ . '/partials/header.php';

// Ajuste aqui:
$SERVER_NAME = $title;
$DISCORD_URL = $discord;
$SUPPORT_URL = "support.php"; // ou um link externo
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($SERVER_NAME) ?> Regras</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
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

  <div class="panel-grid" style="max-width: 1050px;">

    <!-- HERO -->
    <div class="panel-card panel-full">
      <h3 style="margin-bottom:8px;">Regras e Fair Play</h3>
      <p style="margin-top:0;">
        Ao jogar no <strong><?= htmlspecialchars($SERVER_NAME) ?></strong>, você concorda com as regras abaixo.
        Elas existem para proteger a comunidade, garantir competitividade justa e manter a estabilidade do servidor.
      </p>

      <div class="status-box" style="margin-top:14px;">
        <div class="status-row"><span class="label">Versão</span><span class="value">L2J (Interlude)</span></div>
        <div class="status-row"><span class="label">Aplicação</span><span class="value">Todos os jogadores e contas</span></div>
        <div class="status-row"><span class="label">Atualizações</span><span class="value">Podem ocorrer sem aviso prévio</span></div>
      </div>
    </div>

    <!-- 1) CONDUTA -->
    <div class="panel-card panel-full">
      <h3>1) Conduta e Comunidade</h3>
      <ul class="security-list">
        <li><strong>Respeito:</strong> proibido racismo, xenofobia, homofobia, assédio, ameaças, perseguição e discurso de ódio.</li>
        <li><strong>Doxxing:</strong> proibido expor informações pessoais (reais) de terceiros, mesmo “como brincadeira”.</li>
        <li><strong>Spam/Flood:</strong> proibido spam, propaganda não autorizada e flood em chats públicos.</li>
        <li><strong>Impersonação:</strong> proibido se passar por Staff, GM, streamers ou outros jogadores.</li>
        <li><strong>Conteúdo ilegal:</strong> qualquer conteúdo ilegal resulta em banimento imediato.</li>
      </ul>
      <p class="muted" style="margin-top:10px;">
        O Staff pode aplicar sanções para proteger a comunidade, mesmo em casos não previstos explicitamente.
      </p>
    </div>

    <!-- 2) FAIR PLAY -->
    <div class="panel-card panel-full">
      <h3>2) Fair Play e Anti-Cheat</h3>
      <ul class="security-list">
        <li><strong>Bot/Macro/Automação:</strong> proibido qualquer bot, macro, autoclicker, script ou automação não autorizada.</li>
        <li><strong>Cheats/Hacks:</strong> proibido uso de cheat engine, injeção de DLL, mod menu, edits e ferramentas similares.</li>
        <li><strong>Exploit/Bug abuse:</strong> explorar falhas para vantagem é proibido. Bugs devem ser reportados.</li>
        <li><strong>Packet/Inject:</strong> qualquer tentativa de manipulação de pacotes, bypass, ou automação via cliente modificado é proibida.</li>
        <li><strong>Multi-box:</strong> permitido ou proibido conforme política do servidor. Se permitido, limites e regras serão aplicados (ex: máximo X janelas).</li>
      </ul>

      <div class="msg" style="margin-top:12px;background: rgba(255,140,0,0.10); color:#ffdf9f; border:1px solid rgba(255,140,0,0.22);">
        Dica: se você tiver dúvidas sobre um programa/macro, pergunte no suporte antes de usar.
      </div>
    </div>

    <!-- 3) ECONOMIA E TRADES -->
    <div class="panel-card panel-full">
      <h3>3) Economia, Trades e Segurança</h3>
      <ul class="security-list">
        <li><strong>Golpes:</strong> golpes e fraudes em trades (ex: troca “troll”, promessas falsas, chargeback) podem resultar em punição.</li>
        <li><strong>Trades oficiais:</strong> use sistemas oficiais do jogo (trade window, private store) para reduzir riscos.</li>
        <li><strong>RMT:</strong> compra/venda de adena/itens/conta por dinheiro fora do servidor pode ser punida (ban/rollback), conforme política do servidor.</li>
        <li><strong>Conta:</strong> você é responsável por sua conta, senha e segurança do seu PC.</li>
      </ul>
      <p class="muted" style="margin-top:10px;">
        O servidor pode aplicar rollback de itens/moedas em caso de exploração, fraude, duplicação ou comprometimento do ambiente.
      </p>
    </div>

    <!-- 4) DOAÇÕES / LOJA -->
    <div class="panel-card panel-full">
      <h3>4) Doações, Loja e Reembolsos</h3>
      <p>
        O servidor é mantido por custos de infraestrutura (dedicado, rede, proteção, licenças/serviços e manutenção).
        Contribuições financeiras são consideradas <strong>doações voluntárias</strong> para manutenção do projeto.
      </p>

      <ul class="security-list">
        <li><strong>Sem reembolso:</strong> doações são <strong>não reembolsáveis</strong>, inclusive em casos de punição, desistência, wipe, rollback ou mudança de economia.</li>
        <li><strong>Benefícios digitais:</strong> itens/coins/benefícios entregues são digitais e podem ser ajustados por balanceamento.</li>
        <li><strong>Chargeback:</strong> contestação/chargeback pode gerar banimento e bloqueio do acesso por segurança.</li>
        <li><strong>Entregas:</strong> prazos de entrega podem variar; em caso de falhas, o suporte analisará logs e comprovantes.</li>
      </ul>

      <div class="msg" style="margin-top:12px;background: rgba(76,255,154,0.10); color:#bfffe0; border:1px solid rgba(76,255,154,0.16);">
        Transparência: doações ajudam diretamente na manutenção do dedicado, anti-DDoS, backups e melhorias do servidor.
      </div>
    </div>

    <!-- 5) MANUTENÇÃO / DEDICADO -->
    <div class="panel-card panel-full">
      <h3>5) Estabilidade, Manutenção e Infraestrutura</h3>
      <ul class="security-list">
        <li><strong>Manutenção:</strong> podem ocorrer manutenções programadas ou emergenciais para garantir estabilidade e segurança.</li>
        <li><strong>Interrupções:</strong> quedas por rede, datacenter, DDoS, energia ou terceiros podem ocorrer; faremos o possível para reduzir impacto.</li>
        <li><strong>Backups:</strong> backups e rotinas de segurança existem, mas não garantem recuperação total em 100% dos cenários extremos.</li>
        <li><strong>Wipe/Rollback:</strong> pode ser necessário em caso de exploit grave, duplicação, corrupção de dados ou eventos críticos.</li>
      </ul>
      <p class="muted" style="margin-top:10px;">
        Ao jogar, você aceita que serviços online podem sofrer instabilidades eventuais.
      </p>
    </div>

    <!-- 6) PUNIÇÕES -->
    <div class="panel-card panel-full">
      <h3>6) Punições e Aplicação</h3>
      <p>As punições variam conforme gravidade e reincidência:</p>
      <ul class="security-list">
        <li><strong>Advertência:</strong> para infrações leves e primeira ocorrência.</li>
        <li><strong>Mute:</strong> para spam, ofensas e conduta em chat.</li>
        <li><strong>Bloqueio temporário:</strong> para infrações moderadas ou suspeitas em investigação.</li>
        <li><strong>Banimento:</strong> para cheats, bots, exploit, doxxing, ameaças, fraude e reincidência.</li>
        <li><strong>Rollback/remoção de itens:</strong> quando necessário para restaurar integridade da economia.</li>
      </ul>

      <div class="status-box" style="margin-top:14px;">
        <div class="status-row"><span class="label">Provas</span><span class="value">Logs / prints / vídeos / auditoria</span></div>
        <div class="status-row"><span class="label">Decisão</span><span class="value">Staff pode ser final</span></div>
      </div>
    </div>

    <!-- 7) PRIVACIDADE -->
    <div class="panel-card panel-full">
      <h3>7) Privacidade e Logs</h3>
      <ul class="security-list">
        <li><strong>Logs técnicos:</strong> registramos IP, ações de segurança, tentativas de login e eventos de auditoria para proteção do serviço.</li>
        <li><strong>Compartilhamento:</strong> não vendemos dados; podemos compartilhar informações apenas quando exigido por lei ou para mitigar fraude/ataques.</li>
        <li><strong>Cliente:</strong> softwares de proteção podem coletar informações técnicas do ambiente para anti-cheat (ex: integridade do cliente).</li>
      </ul>
    </div>

    <!-- 8) DISPOSIÇÕES FINAIS -->
    <div class="panel-card panel-full">
      <h3>8) Disposições Finais</h3>
      <ul class="security-list">
        <li>As regras podem ser atualizadas para manter o equilíbrio e segurança do servidor.</li>
        <li>Não conhecer as regras não isenta o jogador de punições.</li>
        <li>Em caso de dúvida, procure o suporte antes de tomar qualquer ação.</li>
      </ul>

      <div class="community-actions community-actions--mmorpg" style="margin-top:14px;">
        <a class="soc-btn soc-discord is-primary" href="<?= htmlspecialchars($DISCORD_URL) ?>" target="_blank" rel="noopener">
          <span>Discord / Comunidade</span>
        </a>

        <a class="soc-btn" href="<?= htmlspecialchars($SUPPORT_URL) ?>">
          <span>Central de Suporte</span>
        </a>

        <a class="soc-btn" href="index.php">
          <span>Voltar ao Início</span>
        </a>

        <a class="soc-btn" href="login.php">
          <span>Ir para Login</span>
        </a>
      </div>
    </div>

  </div>
  </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
<button id="toTop" class="to-top" aria-label="Voltar ao topo" title="Voltar ao topo"></button>

</body>
</html>
