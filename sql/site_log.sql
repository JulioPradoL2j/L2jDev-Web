CREATE TABLE site_log (
    id INT AUTO_INCREMENT PRIMARY KEY,

    ip VARCHAR(45) NOT NULL,
    account VARCHAR(45) DEFAULT NULL,

    action VARCHAR(32) NOT NULL,
    details VARCHAR(255) DEFAULT NULL,

    time INT NOT NULL,

    INDEX idx_ip_time (ip, time),
    INDEX idx_action_time (action, time),
    INDEX idx_account_time (account, time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
