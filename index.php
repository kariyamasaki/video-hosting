<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Получение видео
$stmt = $pdo->prepare("
    SELECT v.*, u.username, 
    (SELECT COUNT(*) FROM likes WHERE video_id = v.id) as like_count,
    (SELECT COUNT(*) FROM likes WHERE video_id = v.id AND user_id = ?) as user_liked
    FROM videos v 
    JOIN users u ON v.user_id = u.id 
    ORDER BY v.uploaded_at DESC
");
$stmt->execute([getUserID()]);
$videos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Hosting - Home</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial; margin: 0; background: #f9f9f9; }
        
        /* Header */
        header { background: #ff0000; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 24px; font-weight: bold; }
        .user-info { display: flex; align-items: center; gap: 15px; }
        .logout { background: white; color: #ff0000; padding: 8px 15px; text-decoration: none; border-radius: 5px; }
        
        /* Main Content */
        .container { display: flex; padding: 20px; gap: 30px; }
        
        /* Upload Form */
        .upload-section { flex: 1; max-width: 400px; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .upload-section h2 { margin-top: 0; }
        .upload-form input, .upload-form textarea { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 5px; }
        .upload-form button { background: #ff0000; color: white; padding: 12px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        .upload-form button:hover { background: #cc0000; }
        
        /* Video Grid */
        .videos-section { flex: 2; }
        .videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .video-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .video-thumbnail { width: 100%; height: 180px; background: #333; position: relative; }
        .video-info { padding: 15px; }
        .video-title { font-weight: bold; margin: 0 0 10px 0; }
        .video-meta { color: #666; font-size: 14px; margin-bottom: 10px; }
        .like-btn { background: #ff4444; color: white; border: none; padding: 8px 15px; border-radius: 5px; cursor: pointer; }
        .like-btn.liked { background: #cc0000; }
        .likes-count { margin-left: 10px; }
        
        /* Upload Progress */
        #uploadProgress { width: 100%; margin: 10px 0; }
        #uploadStatus { text-align: center; margin-top: 10px; }
    </style>
</head>
<body>
    <header>
        <div class="logo">VideoHosting</div>
        <div class="user-info">
            Welcome, <?php echo $_SESSION['username']; ?>!
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>
    
    <div class="container">
        <!-- Upload Section -->
        <section class="upload-section">
            <h2>Upload Video</h2>
            <form class="upload-form" id="uploadForm" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Video Title" required>
                <textarea name="description" placeholder="Description" rows="3"></textarea>
                <input type="file" name="video" accept="video/*" required>
                <button type="submit">Upload Video</button>
                
                <progress id="uploadProgress" value="0" max="100" style="display:none;"></progress>
                <div id="uploadStatus"></div>
            </form>
        </section>
        
        <!-- Videos Section -->
        <section class="videos-section">
            <h2>All Videos</h2>
            <div class="videos-grid">
                <?php foreach($videos as $video): ?>
                <div class="video-card" data-video-id="<?php echo $video['id']; ?>">
                    <div class="video-thumbnail">
                        <?php if($video['thumbnail']): ?>
                            <img src="uploads/thumbnails/<?php echo $video['thumbnail']; ?>" style="width:100%; height:100%; object-fit:cover;">
                        <?php else: ?>
                            <div style="color:white; text-align:center; padding-top:80px;">No Thumbnail</div>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                        <div class="video-meta">
                            By: <?php echo htmlspecialchars($video['username']); ?><br>
                            Views: <?php echo $video['views']; ?> | 
                            Uploaded: <?php echo date('M d, Y', strtotime($video['uploaded_at'])); ?>
                        </div>
                        <button class="like-btn <?php echo $video['user_liked'] ? 'liked' : ''; ?>" 
                                onclick="toggleLike(<?php echo $video['id']; ?>)">
                            <?php echo $video['user_liked'] ? 'Unlike' : 'Like'; ?>
                        </button>
                        <span class="likes-count"><?php echo $video['like_count']; ?> likes</span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
    
    <script>
        // AJAX загрузка видео
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressBar = document.getElementById('uploadProgress');
            const statusDiv = document.getElementById('uploadStatus');
            
            progressBar.style.display = 'block';
            statusDiv.textContent = 'Uploading...';
            
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = (e.loaded / e.total) * 100;
                    progressBar.value = percent;
                }
            });
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        statusDiv.innerHTML = '<span style="color:green;">Upload successful! Refreshing...</span>';
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        statusDiv.innerHTML = '<span style="color:red;">Error: ' + response.message + '</span>';
                    }
                } else {
                    statusDiv.innerHTML = '<span style="color:red;">Upload failed</span>';
                }
                progressBar.style.display = 'none';
            };
            
            xhr.open('POST', 'upload.php');
            xhr.send(formData);
        });
        
        // Функция для лайков
        function toggleLike(videoId) {
            const likeBtn = event.target;
            const likesCount = likeBtn.nextElementSibling;
            
            fetch('like.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'video_id=' + videoId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.action === 'liked') {
                        likeBtn.textContent = 'Unlike';
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.textContent = 'Like';
                        likeBtn.classList.remove('liked');
                    }
                    likesCount.textContent = data.likes_count + ' likes';
                }
            });
        }
    </script>
</body>
</html>