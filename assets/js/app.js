
(function () {
  const chars = window.__chars || [];
  const modal = document.getElementById("charModal");
  const body = document.getElementById("charModalBody");
  const title = document.getElementById("charModalTitle");

  if (!modal) return;

  function esc(s) {
    return String(s ?? "")
      .replaceAll("&", "&amp;")
      .replaceAll("<", "&lt;")
      .replaceAll(">", "&gt;")
      .replaceAll('"', "&quot;")
      .replaceAll("'", "&#039;");
  }

  function openModal(charId) {
    const c = chars.find(x => Number(x.obj_Id) === Number(charId));

    if (!c) return;

    title.textContent = `Personagem: ${c.char_name}`;

   const rows = [
 
 
  ["Nome", c.char_name],
  ["Level", c.level],
  ["PvP Kills", c.pvpkills],
  ["PK Kills", c.pkkills],
  ["Karma", c.karma],
  ["Title", c.title || "-"],
  ["Clan ID", c.clanid ?? "-"],
  ["Class ID", c.classid ?? "-"],
  ["Base Class", c.base_class ?? "-"],
  ["Race", c.race ?? "-"],
  ["Sex", c.sex ?? "-"],
  ["Online", (Number(c.online) === 1 ? "Sim" : "Não")],
  ["Online Time (s)", c.onlinetime ?? "-"],
  ["Nobless", Number(c.nobless) === 1 ? "Sim" : "Não"],
  ["Hero", Number(c.hero) === 1 ? "Sim" : "Não"],
  ["VIP", Number(c.vip) === 1 ? "Sim" : "Não"],
  ["VIP End", c.vip_end ?? "-"],
  ["AIO", Number(c.aio) === 1 ? "Sim" : "Não"],
  ["AIO End", c.aio_end ?? "-"],
  ["Last Access", c.lastAccess ?? "-"],
];


    body.innerHTML = `
      <table class="char-table">
        <thead>
          <tr><th>Campo</th><th>Valor</th></tr>
        </thead>
        <tbody>
          ${rows.map(([k,v]) => `<tr><td>${esc(k)}</td><td>${esc(v)}</td></tr>`).join("")}
        </tbody>
      </table>
    `;

    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-open");
  }

  function closeModal() {
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-open");
  }

  document.addEventListener("click", (e) => {
    const tile = e.target.closest(".char-tile");
    if (tile) {
      openModal(tile.getAttribute("data-char"));
      return;
    }
    if (e.target && e.target.getAttribute("data-close") === "1") {
      closeModal();
      return;
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });
})();


document.addEventListener('DOMContentLoaded', () => {
  const audio = document.getElementById('bgMusic');
  const btn   = document.getElementById('musicToggle');
  if (!audio || !btn) return;

  const KEY_MUTED = 'musicMuted';                 // localStorage (preferência)
  const KEY_TIME  = 'musicTime_theme_mp3';        // sessionStorage (posição)
  const KEY_PLAY  = 'musicWasPlaying';            // sessionStorage (estado)

  // 1) Preferência de mute (persistente)
  audio.muted = localStorage.getItem(KEY_MUTED) === '1';

  // 2) Restaurar posição (mesma aba)
  const savedTime = parseFloat(sessionStorage.getItem(KEY_TIME) || '0');
  if (!Number.isNaN(savedTime) && savedTime > 0) {
    // Espera metadados para setar currentTime com segurança
    audio.addEventListener('loadedmetadata', () => {
      // Evita setar além do duration
      if (audio.duration && savedTime < audio.duration) {
        audio.currentTime = savedTime;
      }
    }, { once: true });
  }

  function syncUI() {
    const muted = audio.muted;
    btn.classList.toggle('muted', muted);
    btn.textContent = muted ? '🔇' : '🔊';
    btn.title = muted ? 'Ativar música' : 'Silenciar música';
    btn.setAttribute('aria-label', btn.title);
  }

  syncUI();

  // 3) Tentar tocar (respeita bloqueio de autoplay)
  async function safePlay() {
    if (audio.muted) return;
    try {
      await audio.play();
      sessionStorage.setItem(KEY_PLAY, '1');
    } catch (e) {
      // Autoplay bloqueado -> não força, só marca como "não tocando"
      sessionStorage.setItem(KEY_PLAY, '0');
    }
  }

  // 4) Salvar tempo continuamente (para troca de página)
  const saveTime = () => {
    // Só salva se já tiver carregado algo
    if (!Number.isFinite(audio.currentTime)) return;
    sessionStorage.setItem(KEY_TIME, String(audio.currentTime));
  };

  audio.addEventListener('timeupdate', saveTime);

  // 5) Pausar quando a aba some / voltar quando aparece (continua do mesmo ponto)
  document.addEventListener('visibilitychange', async () => {
    if (document.hidden) {
      // Aba em segundo plano: pausa e salva estado
      sessionStorage.setItem(KEY_PLAY, (!audio.paused && !audio.muted) ? '1' : '0');
      saveTime();
      audio.pause();
      return;
    }

    // Voltou para a aba: volta a tocar SOMENTE se antes estava tocando
    const wasPlaying = sessionStorage.getItem(KEY_PLAY) === '1';
    if (wasPlaying && !audio.muted) {
      await safePlay();
    }
  });

  // 6) Antes de sair da página (troca de rota), salva tudo
  window.addEventListener('pagehide', () => {
    saveTime();
    sessionStorage.setItem(KEY_PLAY, (!audio.paused && !audio.muted) ? '1' : '0');
  });

  // 7) Botão mute/unmute
  btn.addEventListener('click', async () => {
    audio.muted = !audio.muted;
    localStorage.setItem(KEY_MUTED, audio.muted ? '1' : '0');
    syncUI();

    if (!audio.muted) {
      await safePlay(); // clique do usuário libera play
    } else {
      audio.pause();
      sessionStorage.setItem(KEY_PLAY, '0');
    }
  });

  // 8) Start: tocar apenas se a aba estiver visível e não estiver mutado
  if (!document.hidden) {
    // Se o usuário já estava ouvindo antes nesta aba, tenta continuar
    const wasPlaying = sessionStorage.getItem(KEY_PLAY);
    if (wasPlaying === null || wasPlaying === '1') {
      safePlay();
    }
  }
});
document.addEventListener('DOMContentLoaded', () => {
  const chk  = document.getElementById('useSuffix');      // pode não existir
  const row  = document.getElementById('suffixRow');      // pode não existir
  const sel  = document.getElementById('suffixSelect') || (row ? row.querySelector('select') : null);
  const hint = document.getElementById('loginHint');      // agora existe

  // Se não existir select/row, não há o que controlar
  if (!row || !sel) return;

  // Se não existe checkbox, sufixo é obrigatório => sempre ON
  const sync = () => {
    const on = chk ? chk.checked : true;

    row.classList.toggle('active', on);
    if (hint) hint.classList.toggle('active', on);

    sel.disabled = !on;
    if (!on) sel.value = '';
  };

  if (chk) chk.addEventListener('change', sync);
  sync();
});

document.addEventListener('DOMContentLoaded', () => {
  const chk  = document.getElementById('useSuffixLogin'); // pode não existir
  const row  = document.getElementById('suffixRowLogin');
  const sel  = document.getElementById('suffixSelectLogin') || (row ? row.querySelector('select') : null);
  const hint = document.getElementById('loginHintLogin');

  if (!row || !sel) return; // sufixo desativado nessa página

  const sync = () => {
    const on = chk ? chk.checked : true; // sem checkbox => obrigatório

    row.classList.toggle('active', on);
    if (hint) hint.classList.toggle('active', on);

    sel.disabled = !on;
    if (!on) sel.value = '';
  };

  if (chk) chk.addEventListener('change', sync);
  sync();
});

(() => {
  const btn = document.getElementById("toTop");
  if (!btn) return;

  const SHOW_AFTER = 420; // px

  const toggle = () => {
    if (window.scrollY > SHOW_AFTER) btn.classList.add("is-visible");
    else btn.classList.remove("is-visible");
  };

  window.addEventListener("scroll", toggle, { passive: true });
  toggle();

  btn.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
})();


(() => {
  document.addEventListener("click", (e) => {
    const btn = e.target.closest(".clan-more");
    if (!btn) return;

    const castleId = btn.getAttribute("data-castle");
    const all = document.getElementById(`clans_${castleId}_all`);
    if (!all) return;

    const isOpen = !all.classList.contains("is-hidden");
    all.classList.toggle("is-hidden", isOpen);

    btn.textContent = isOpen
      ? `Ver todos (${btn.getAttribute("data-total")})`
      : "Mostrar menos";
  });
})();
// Toggle details
document.addEventListener("click", e => {
  const row = e.target.closest(".boss-row");
  if (row) row.classList.toggle("open");
});

// Search filter
document.querySelectorAll(".boss-search").forEach(input => {
  input.addEventListener("input", () => {
    const q = input.value.toLowerCase();
    const target = input.dataset.target === "grand"
      ? "#grand-bosses .boss-row"
      : "#raid-bosses .boss-row";

    document.querySelectorAll(target).forEach(row => {
      row.style.display = row.dataset.name.includes(q) ? "" : "none";
    });
  });
});

document.addEventListener("DOMContentLoaded", () => {
  const perPage = Number(window.RAID_PER_PAGE || 20);

  const raidList = document.getElementById("raid-bosses");
  const raidRows = raidList ? Array.from(raidList.querySelectorAll(".boss-row")) : [];

  const prevBtn = document.getElementById("raid-prev");
  const nextBtn = document.getElementById("raid-next");
  const infoEl  = document.getElementById("raid-page-info");

  let page = 1;

  function getVisibleRaidRows() {
    // Só conta os que NÃO estão filtrados pela busca
    return raidRows.filter(r => !r.classList.contains("is-filtered"));
  }

  function renderRaidPage() {
    const visible = getVisibleRaidRows();
    const totalPages = Math.max(1, Math.ceil(visible.length / perPage));

    // Corrige page se saiu do range
    if (page > totalPages) page = totalPages;
    if (page < 1) page = 1;

    // Esconde tudo
    raidRows.forEach(r => (r.style.display = "none"));

    // Mostra apenas o slice da página atual (somente visíveis)
    const start = (page - 1) * perPage;
    const end = start + perPage;
    visible.slice(start, end).forEach(r => (r.style.display = ""));

    if (infoEl) {
      infoEl.textContent = `Página ${page} / ${totalPages} • Mostrando ${Math.min(end, visible.length)} de ${visible.length}`;
    }

    if (prevBtn) prevBtn.disabled = (page <= 1);
    if (nextBtn) nextBtn.disabled = (page >= totalPages);
  }

  if (prevBtn) prevBtn.addEventListener("click", () => { page--; renderRaidPage(); });
  if (nextBtn) nextBtn.addEventListener("click", () => { page++; renderRaidPage(); });

  // Busca (RAID e GRAND) - mantém o seu HTML com data-target="raid"/"grand"
  document.querySelectorAll(".boss-search").forEach(input => {
    input.addEventListener("input", () => {
      const q = (input.value || "").trim().toLowerCase();
      const target = input.dataset.target;

      const container = document.getElementById(target === "raid" ? "raid-bosses" : "grand-bosses");
      if (!container) return;

      const rows = Array.from(container.querySelectorAll(".boss-row"));
      rows.forEach(row => {
        const name = (row.dataset.name || "").toLowerCase();
        const match = !q || name.includes(q);
        row.classList.toggle("is-filtered", !match);
      });

      // Se for RAID, reset de página e recalcula paginação
      if (target === "raid") {
        page = 1;
        renderRaidPage();
      } else {
        // GRAND não pagina: só esconde/mostra
        rows.forEach(row => {
          row.style.display = row.classList.contains("is-filtered") ? "none" : "";
        });
      }
    });
  });

  // Inicial
  renderRaidPage();
});
