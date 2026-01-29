ALTER TABLE accounts
ADD balance INT NOT NULL DEFAULT 0 COMMENT 'Saldo da conta (moeda do site)',
ADD last_ip VARCHAR(45),
ADD failed_logins INT NOT NULL DEFAULT 0,
ADD last_failed INT NOT NULL DEFAULT 0,
ADD password_site VARCHAR(255) NULL;
