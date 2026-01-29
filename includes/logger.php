<?php

function app_log(string $message, array $context = []): void
{
    global $config;

    if (empty($config['debug']['enabled'])) {
        return;
    }

    $logFile = $config['debug']['log_file'] ?? null;
    if (!$logFile) return;

    $time = date('Y-m-d H:i:s');

    if (!empty($context)) {
        $message .= ' | ' . json_encode($context, JSON_UNESCAPED_UNICODE);
    }

    file_put_contents(
        $logFile,
        "[$time] $message\n",
        FILE_APPEND
    );
}
