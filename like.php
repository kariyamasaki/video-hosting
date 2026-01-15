<?php
require_once 'config.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = getUserID();
    $video_id = $_POST['video_id'];
    
    // Проверяем, есть ли уже лайк
    $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND video_id = ?");
    $stmt->execute([$user_id, $video_id]);
    
    if ($stmt->rowCount() > 0) {
        // Убираем лайк
        $stmt = $pdo->prepare("DELETE FROM likes WHERE user_id = ? AND video_id = ?");
        $stmt->execute([$user_id, $video_id]);
        $action = 'unliked';
    } else {
        // Ставим лайк
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
        'likes_count' => $result['count']
    ]);
}
?>