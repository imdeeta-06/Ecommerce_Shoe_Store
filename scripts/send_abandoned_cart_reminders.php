<?php

require_once __DIR__ . '/../app/Core/App.php';

App\Core\App::bootstrap();
$result = (new App\Services\AbandonedCartReminderService())->process((int)($_SERVER['argv'][1] ?? 50));
fwrite(STDOUT, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . PHP_EOL);
exit($result['success'] ? 0 : 1);
