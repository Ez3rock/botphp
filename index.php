<?php
// ==================================================
// License Checker for Render.com (PHP)
// ==================================================

// ---------- НАСТРОЙКИ ----------
// Список действительных ключей (можете добавлять свои)
$licenses = [
    'MASQ-TEST-2026' => [
        'expires' => '2026-12-31',
        'hwid'    => ''          // HWID заполнится автоматически при первой активации
    ],
    // Добавляйте новые ключи по образцу:
    // 'НОВЫЙ-КЛЮЧ' => ['expires' => '2027-01-01', 'hwid' => ''],
];

// Файл для хранения логов (будет создан автоматически)
define('LOG_FILE', __DIR__ . '/license_log.txt');

// ---------- ФУНКЦИИ ----------
function logMessage($msg) {
    file_put_contents(LOG_FILE, date('Y-m-d H:i:s') . ' - ' . $msg . PHP_EOL, FILE_APPEND);
}

function sendJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// ---------- ОБРАБОТКА ЗАПРОСА ----------
// Получаем JSON от DLL
$input = json_decode(file_get_contents('php://input'), true);
$key   = $input['key'] ?? '';
$hwid  = $input['hwid'] ?? '';

logMessage("Request: key=$key, hwid=$hwid");

// Проверка наличия ключа
if (!isset($licenses[$key])) {
    logMessage("Key not found: $key");
    sendJson(['success' => false, 'message' => 'Invalid license key']);
}

$license = &$licenses[$key];

// Проверка срока действия
if (date('Y-m-d') > $license['expires']) {
    logMessage("Key expired: $key");
    sendJson(['success' => false, 'message' => 'License expired']);
}

// Если HWID ещё не привязан — активируем
if (empty($license['hwid'])) {
    $license['hwid'] = $hwid;
    // Сохраняем обновлённый массив (простая запись в тот же файл — но массив в коде не сохранится!)
    // Для постоянного хранения HWID нужно использовать базу данных или запись в отдельный файл.
    // Упрощённо: запишем HWID в отдельный файл рядом.
    file_put_contents(__DIR__ . "/keys/$key.hwid", $hwid);
    logMessage("Key activated: $key with HWID $hwid");
    sendJson(['success' => true, 'message' => 'License activated']);
}

// Проверка HWID
if ($license['hwid'] === $hwid) {
    logMessage("Key valid: $key");
    sendJson(['success' => true, 'message' => 'License valid']);
} else {
    logMessage("HWID mismatch for key: $key (expected {$license['hwid']}, got $hwid)");
    sendJson(['success' => false, 'message' => 'This key is already used on another computer']);
}
?>
