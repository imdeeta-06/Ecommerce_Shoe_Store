<?php

require_once __DIR__ . '/../app/Core/App.php';
\App\Core\App::bootstrap();

$result = (new \App\Services\OrderNotificationService())->process(100);
echo $result['message'] . PHP_EOL;
exit($result['success'] ? 0 : 1);
