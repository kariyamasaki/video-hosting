<?php
// check_config.php - проверка конфигурации
echo '<h2>Проверка конфигурации сервера</h2>';
echo '<pre>';

// Проверка сессии
session_start();
echo "Сессия ID: " . session_id() . "\n";
echo "Сессия стартовала: " . (isset($_SESSION) ? 'ДА' : 'НЕТ') . "\n\n";

// Проверка PHP настроек
$settings = [
    'upload_max_filesize',
    'post_max_size', 
    'max_execution_time',
    'max_input_time',
    'memory_limit',
    'file_uploads',
    'upload_tmp_dir'
];

foreach ($settings as $setting) {
    echo $setting . ": " . ini_get($setting) . "\n";
}

echo "\nВременная папка: " . sys_get_temp_dir() . "\n";
echo "Доступна для записи: " . (is_writable(sys_get_temp_dir()) ? 'ДА' : 'НЕТ') . "\n";

// Проверка GD библиотеки (для создания миниатюр)
echo "\nGD библиотека: " . (function_exists('imagecreatefromjpeg') ? '✅ Установлена' : '❌ Отсутствует') . "\n";

// Проверка PDO
try {
    $pdo = new PDO('mysql:host=localhost;dbname=video_hosting', 'root', '');
    echo "PDO MySQL: ✅ Подключено\n";
} catch (Exception $e) {
    echo "PDO MySQL: ❌ Ошибка: " . $e->getMessage() . "\n";
}

echo '</pre>';
?>