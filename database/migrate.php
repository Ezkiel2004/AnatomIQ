<?php
/** Non-destructive, repeatable schema upgrades. Run from the command line. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/connection.php';
$pdo = Database::getInstance()->getConnection();
$pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (setting_key VARCHAR(80) PRIMARY KEY, setting_value TEXT NOT NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS anatomy_content (
 system_id INT UNSIGNED PRIMARY KEY, model_url VARCHAR(500) NULL,
 key_facts LONGTEXT NULL, structures LONGTEXT NULL, source_text TEXT NULL,
 FOREIGN KEY (system_id) REFERENCES body_systems(system_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("CREATE TABLE IF NOT EXISTS achievement_rules (
 achievement_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY, title VARCHAR(100) NOT NULL,
 metric VARCHAR(50) NOT NULL, threshold DECIMAL(10,2) NOT NULL, is_active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
foreach (['deadline_at' => 'DATETIME NULL', 'draft_answers' => 'LONGTEXT NULL'] as $column => $definition) {
 $check = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?');
 $check->execute(['assessment_submissions', $column]);
 if (!$check->fetchColumn()) $pdo->exec("ALTER TABLE assessment_submissions ADD COLUMN `$column` $definition");
}
$pdo->exec("ALTER TABLE student_profiles MODIFY grade_level VARCHAR(20) NOT NULL, MODIFY school_year VARCHAR(20) NOT NULL DEFAULT ''");
$pdo->exec("ALTER TABLE announcements MODIFY audience VARCHAR(200) NOT NULL DEFAULT 'all'");
$pdo->exec("CREATE TABLE IF NOT EXISTS notification_receipts (
 user_id INT UNSIGNED NOT NULL, notification_key VARCHAR(80) NOT NULL,
 read_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(user_id, notification_key),
 FOREIGN KEY(user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
echo "Schema migration complete. Existing accounts and learning records preserved.\n";
