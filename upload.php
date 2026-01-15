<?php
require_once 'config.php';

// Проверяем авторизацию
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

// Обрабатываем только POST запросы
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

// Проверяем загружен ли файл
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== UPLOAD_ERR_OK) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit();
}

// Получаем данные формы
$title = $_POST['title'] ?? 'Untitled Video';
$description = $_POST['description'] ?? '';
$user_id = $_SESSION['user_id'];

// Проверяем расширение файла
$allowedExtensions = ['mp4', 'avi', 'mov', 'wmv'];
$filename = $_FILES['video']['name'];
$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

if (!in_array($extension, $allowedExtensions)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: MP4, AVI, MOV, WMV']);
    exit();
}

// Проверяем размер файла (100MB максимум)
$maxFileSize = 100 * 1024 * 1024; // 100MB
if ($_FILES['video']['size'] > $maxFileSize) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'File is too large. Maximum size: 100MB']);
    exit();
}

// Создаем папки если не существуют
if (!file_exists('uploads/videos')) {
    mkdir('uploads/videos', 0777, true);
}
if (!file_exists('uploads/thumbnails')) {
    mkdir('uploads/thumbnails', 0777, true);
}

// Генерируем уникальное имя файла
$uniqueName = uniqid() . '_' . preg_replace('/[^a-zA-Z0-9\._-]/', '', $filename);
$targetPath = 'uploads/videos/' . $uniqueName;

// Перемещаем загруженный файл
if (move_uploaded_file($_FILES['video']['tmp_name'], $targetPath)) {
    // Генерируем миниатюру (простой вариант)
    $thumbnail = generateThumbnail($uniqueName);
    
    // Сохраняем в базу данных
    try {
        $stmt = $pdo->prepare("
            INSERT INTO videos (title, description, filename, thumbnail, user_id, uploaded_at) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$title, $description, $uniqueName, $thumbnail, $user_id]);
        
        $video_id = $pdo->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'Video uploaded successfully!',
            'video_id' => $video_id
        ]);
        
    } catch (PDOException $e) {
        // Удаляем загруженный файл если ошибка БД
        unlink($targetPath);
        
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
}

function generateThumbnail($filename) {
    $thumbnailName = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $thumbnailPath = 'uploads/thumbnails/' . $thumbnailName;
    
    // Создаем простую миниатюру (черный прямоугольник с текстом)
    $im = imagecreatetruecolor(320, 180);
    $bgColor = imagecolorallocate($im, 40, 40, 40);
    imagefilledrectangle($im, 0, 0, 320, 180, $bgColor);
    
    // Добавляем иконку видео
    $iconColor = imagecolorallocate($im, 255, 0, 0);
    $centerX = 160;
    $centerY = 90;
    
    // Рисуем треугольник (play icon)
    $points = [
        $centerX - 20, $centerY - 20,
        $centerX - 20, $centerY + 20,
        $centerX + 20, $centerY
    ];
    imagefilledpolygon($im, $points, 3, $iconColor);
    
    // Сохраняем изображение
    imagejpeg($im, $thumbnailPath, 80);
    imagedestroy($im);
    
    return $thumbnailName;
}
?>