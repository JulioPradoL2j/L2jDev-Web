CREATE TABLE site_news (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  title        VARCHAR(120) NOT NULL,
  slug         VARCHAR(160) NOT NULL,
  summary      VARCHAR(255) DEFAULT NULL,
  content      MEDIUMTEXT DEFAULT NULL,

  category     VARCHAR(40)  DEFAULT NULL,  -- "Sistema", "Eventos", "Balance", etc
  tags         VARCHAR(120) DEFAULT NULL,  -- "Core, PvP, TvT" (simples por enquanto)
  
  is_published TINYINT(1) NOT NULL DEFAULT 1,
  is_pinned    TINYINT(1) NOT NULL DEFAULT 0,

  published_at DATETIME NULL,
  created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (id),
  UNIQUE KEY uq_site_news_slug (slug),
  KEY idx_site_news_pub (is_published, published_at),
  KEY idx_site_news_pin (is_pinned, published_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_news (title, slug, summary, content, category, tags, is_published, is_pinned, published_at)
VALUES
('Patch 1.0 • Ajustes de estabilidade', 'patch-1-0-ajustes-estabilidade', 'Otimizações gerais e correções críticas.', 'Detalhes do patch...', 'Sistema', 'Core,Performance', 1, 1, NOW()),
('Evento do fim de semana', 'evento-fim-de-semana', 'TvT com reward especial.', 'Regras do evento...', 'Eventos', 'TvT,Reward', 1, 0, NOW());
