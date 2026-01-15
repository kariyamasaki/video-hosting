<?php
// upload_final.php - РАБОЧАЯ ВЕРСИЯ

// ВКЛЮЧАЕМ БУФЕРИЗАЦИЮ ВЫВОДА - ЭТО ВАЖНО!
ob_start();

// Стартуем сессию
session_start();

// Очищаем буфер от любых случайных данных
ob_clean();

// Устанавливаем заголовок
header('Content-Type: application/json; charset=utf-8');

// ========== ФУНКЦИЯ ДЛЯ ВОЗВРАТА ОТВЕТА ==========
function sendResponse($success, $message, $data = []) {
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    
    // Очищаем буфер и отправляем JSON
    ob_clean();
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// ========== ПРОВЕРКА АВТОРИЗАЦИИ ==========
if (!isset($_SESSION['user_id'])) {
    sendResponse(false, 'Требуется авторизация');
}

$user_id = $_SESSION['user_id'];

// ========== ПРОВЕРКА МЕТОДА ==========
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse(false, 'Неверный метод запроса');
}

// ========== ПРОВЕРКА ФАЙЛА ==========
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    sendResponse(false, 'Ошибка загрузки файла. Код ошибки: ' . ($_FILES['video']['error'] ?? 'неизвестно'));
}

// ========== ПОЛУЧАЕМ ДАННЫЕ ==========
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$file = $_FILES['video'];

if (empty($title)) {
    sendResponse(false, 'Введите название видео');
}

// ========== СОЗДАЕМ ПАПКИ ==========
$folders = ['uploads', 'uploads/videos', 'uploads/thumbnails'];
foreach ($folders as $folder) {
    if (!file_exists($folder)) {
        if (!mkdir($folder, 0777, true)) {
            sendResponse(false, 'Не удалось создать папку: ' . $folder);
        }
    }
}

// ========== СОХРАНЯЕМ ФАЙЛ ==========
$filename = 'video_' . time() . '_' . rand(1000, 9999) . '.mp4';
$filepath = 'uploads/videos/' . $filename;

if (move_uploaded_file($file['tmp_name'], $filepath)) {
    // ========== СОЗДАЕМ МИНИАТЮРУ ==========
    $thumbnail = 'thumb_' . time() . '.jpg';
    $thumbpath = 'uploads/thumbnails/' . $thumbnail;
    
    // Создаем красную миниатюру
    $image = imagecreatetruecolor(320, 180);
    $color = imagecolorallocate($image, 255, 0, 0);
    imagefilledrectangle($image, 0, 0, 320, 180, $color);
    imagejpeg($image, $thumbpath, 80);
    imagedestroy($image);
    
    // ========== СОХРАНЯЕМ В БАЗУ ==========
    try {
        $host = 'localhost';
        $dbname = 'video_hosting';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
        
        $stmt = $pdo->prepare("
            INSERT INTO videos (title, description, filename, thumbnail, user_id, uploaded_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            htmlspecialchars($title),
            htmlspecialchars($description),
            $filename,
            $thumbnail,
            $user_id
        ]);
        
        // УСПЕШНЫЙ ОТВЕТ
        sendResponse(true, 'Видео успешно загружено!', [
            'filename' => $filename,
            'video_id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        // Удаляем файлы если ошибка БД
        if (file_exists($filepath)) unlink($filepath);
        if (file_exists($thumbpath)) unlink($thumbpath);
        
        sendResponse(false, 'Ошибка базы данных: ' . $e->getMessage());
    }
    
} else {
    sendResponse(false, 'Ошибка сохранения файла на сервере');
}
?>