<?php
// test_upload_only.php - только загрузка файла, без всего лишнего
session_start();
$_SESSION['user_id'] = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Очищаем ВСЕ
    while (ob_get_level()) ob_end_clean();
    
    // Только чистый JSON
    header('Content-Type: application/json');
    
    if (isset($_FILES['video'])) {
        $target = 'uploads/videos/test_' . time() . '.mp4';
        
        if (move_uploaded_file($_FILES['video']['tmp_name'], $target)) {
            echo json_encode(['success' => true, 'message' => 'Файл сохранен: ' . $target]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Ошибка сохранения']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Файл не получен']);
    }
    
    exit;
}
?>
<form method="post" enctype="multipart/form-data">
    <input type="file" name="video">
    <button>Тест чистого JSON</button>
</form>