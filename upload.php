<?php
// upload.php - УПРОЩЕННЫЙ И РАБОЧИЙ
session_start();

// Подключение к базе данных
$host = 'localhost';
$dbname = 'video_hosting';
$username = 'root';
$password = '';

// Создаем подключение
$pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Устанавливаем заголовок JSON
header('Content-Type: application/json');

// Проверка авторизации
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Требуется авторизация']);
    exit();
}

$user_id = $_SESSION['user_id'];

// Проверка метода
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Неверный метод']);
    exit();
}

// Проверка файла
if (!isset($_FILES['video']) || $_FILES['video']['error'] !== 0) {
    echo json_encode(['success' => false, 'message' => 'Ошибка загрузки файла']);
    exit();
}

// Получаем данные
$title = $_POST['title'] ?? 'Без названия';
$description = $_POST['description'] ?? '';
$file = $_FILES['video'];

// Создаем папки если нет
if (!is_dir('uploads')) mkdir('uploads');
if (!is_dir('uploads/videos')) mkdir('uploads/videos', 0777, true);
if (!is_dir('uploads/thumbnails')) mkdir('uploads/thumbnails', 0777, true);

// Генерируем имя файла
$filename = time() . '_' . rand(1000, 9999) . '.mp4';
$target_path = 'uploads/videos/' . $filename;

// Сохраняем файл
if (move_uploaded_file($file['tmp_name'], $target_path)) {
    
    // Создаем миниатюру
    $thumbnail = 'thumbnail_' . time() . '.jpg';
    $thumb_path = 'uploads/thumbnails/' . $thumbnail;
    
    // Простая миниатюра
    $img = imagecreatetruecolor(320, 180);
    $bg = imagecolorallocate($img, 255, 0, 0); // Красный фон
    imagefilledrectangle($img, 0, 0, 320, 180, $bg);
    imagejpeg($img, $thumb_path, 80);
    imagedestroy($img);
    
    // Сохраняем в базу
    $stmt = $pdo->prepare("
        INSERT INTO videos (title, description, filename, thumbnail, user_id, uploaded_at) 
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([$title, $description, $filename, $thumbnail, $user_id]);
    
    // УСПЕШНЫЙ ОТВЕТ
    $response = [
        'success' => true,
        'message' => 'Видео загружено успешно!',
        'filename' => $filename
    ];
    
    echo json_encode($response);
    
} else {
    echo json_encode(['success' => false, 'message' => 'Ошибка сохранения файла']);
}
?>