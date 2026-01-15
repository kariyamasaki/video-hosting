<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $user_id = getUserID();
    
    // Создание папок если нет
    if (!file_exists('uploads/videos')) mkdir('uploads/videos', 0777, true);
    if (!file_exists('uploads/thumbnails')) mkdir('uploads/thumbnails', 0777, true);
    
    // Загрузка видео
    $videoFile = $_FILES['video'];
    $filename = uniqid() . '_' . basename($videoFile['name']);
    $targetPath = 'uploads/videos/' . $filename;
    
    if (move_uploaded_file($videoFile['tmp_name'], $targetPath)) {
        // Генерация миниатюры (первый кадр)
        $thumbnail = generateThumbnail($targetPath, $filename);
        
        // Сохранение в БД
        $stmt = $pdo->prepare("INSERT INTO videos (title, description, filename, thumbnail, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $filename, $thumbnail, $user_id]);
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Upload failed']);
    }
}

function generateThumbnail($videoPath, $filename) {
    // Для генерации миниатюр нужно установить ffmpeg
    // Временное решение - создаем placeholder
    $thumbnailName = pathinfo($filename, PATHINFO_FILENAME) . '.jpg';
    $thumbnailPath = 'uploads/thumbnails/' . $thumbnailName;
    
    // Создаем черный прямоугольник как placeholder
    $im = imagecreatetruecolor(320, 180);
    $black = imagecolorallocate($im, 0, 0, 0);
    imagefilledrectangle($im, 0, 0, 320, 180, $black);
    imagejpeg($im, $thumbnailPath, 80);
    imagedestroy($im);
    
    return $thumbnailName;
}
?>