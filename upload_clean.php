<?php
// upload_clean.php - ЧИСТЫЙ JSON БЕЗ ЛИШНИХ СИМВОЛОВ

// 1. ВКЛЮЧАЕМ БУФЕРИЗАЦИЮ САМОГО НАЧАЛА
if (ob_get_level()) ob_end_clean();
ob_start();

// 2. СТАРТУЕМ СЕССИЮ
session_start();

// 3. УСТАНАВЛИВАЕМ ЗАГОЛОВОК
header('Content-Type: application/json');

// 4. ФУНКЦИЯ ДЛЯ ОТВЕТА
function json_response($success, $message, $data = []) {
    $response = ['success' => $success, 'message' => $message];
    if ($data) $response['data'] = $data;
    
    // Очищаем ВСЕ буферы
    while (ob_get_level()) ob_end_clean();
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

// 5. ПРОВЕРКА АВТОРИЗАЦИИ
if (!isset($_SESSION['user_id'])) {
    json_response(false, 'Не авторизован');
}

// 6. ПРОВЕРКА МЕТОДА
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'Только POST');
}

// 7. ПРОВЕРКА ФАЙЛА
if (!isset($_FILES['video'])) {
    json_response(false, 'Файл не получен');
}

// 8. СОХРАНЕНИЕ ФАЙЛА
$target_dir = 'uploads/videos/';
if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

$filename = 'video_' . time() . '_' . rand(1000, 9999) . '.mp4';
$target_path = $target_dir . $filename;

// Пробуем сохранить
if (move_uploaded_file($_FILES['video']['tmp_name'], $target_path)) {
    
    // 9. СОХРАНЕНИЕ В БАЗУ (УПРОЩЕННОЕ)
    try {
        $pdo = new PDO('mysql:host=localhost;dbname=video_hosting', 'root', '');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $title = $_POST['title'] ?? 'Видео';
        $description = $_POST['description'] ?? '';
        $user_id = $_SESSION['user_id'];
        
        $stmt = $pdo->prepare("
            INSERT INTO videos (title, description, filename, user_id, uploaded_at) 
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([$title, $description, $filename, $user_id]);
        
        // 10. УСПЕШНЫЙ ОТВЕТ
        json_response(true, 'Видео загружено!', [
            'filename' => $filename,
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch (Exception $e) {
        // Если ошибка БД, удаляем файл
        if (file_exists($target_path)) unlink($target_path);
        json_response(false, 'Ошибка БД: ' . $e->getMessage());
    }
    
} else {
    json_response(false, 'Ошибка сохранения файла');
}

// 11. ЕСЛИ ЧТО-ТО ПОШЛО НЕ ТАК
json_response(false, 'Неизвестная ошибка');
?>