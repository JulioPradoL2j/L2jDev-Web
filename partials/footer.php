<?php
$config = $config ?? (file_exists(__DIR__ . '/../config.php') ? require __DIR__ . '/../config.php' : ['site'=>['name'=>'Site']]);
?>
<footer>
    <p>© <?= date('Y'); ?> <?= htmlspecialchars($config['site']['name']); ?></p>
</footer>
