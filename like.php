<?php
require_once 'config.php';

// Проверка авторизации
if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = getUserID();
    $video_id = (int)($_POST['video_id'] ?? 0);
    
    if ($video_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid video ID']);
        exit();
    }
    
    try {
        // Проверяем, лайкал ли уже пользователь
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        
        if ($stmt->rowCount() > 0) {
            // Удаляем лайк
            $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?");
            $stmt->execute([$user_id, $video_id]);
            $action = 'unliked';
        } else {
            // Добавляем лайк
            $stmt = $pdo->prepare("INSERT INTO likes (user_id, video_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $video_id]);
            $action = 'liked';
        }
        
        // Получаем количество лайков
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM likes WHERE video_id = ?");
        $stmt->execute([$video_id]);
        $result = $stmt->fetch();
        
        echo json_encode([
            'success' => true,
            'action' => $action,
            'likes_count' => (int)$result['count']
        ]);
        
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false, 
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
}
?>