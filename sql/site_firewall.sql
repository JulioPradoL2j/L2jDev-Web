CREATE TABLE IF NOT EXISTS site_firewall (
  ip           VARCHAR(45) NOT NULL,
  blocked_until INT UNSIGNED NOT NULL,
  reason       VARCHAR(32) NOT NULL DEFAULT 'unknown',
  PRIMARY KEY (ip)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
