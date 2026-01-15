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
        .upload-form input, .upload-form textarea, .upload-form select { 
            width: 100%; 
            padding: 10px; 
            margin: 10px 0; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-family: Arial;
        }
        .upload-form textarea { resize: vertical; }
        .upload-form button { 
            background: #ff0000; 
            color: white; 
            padding: 12px; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer; 
            width: 100%; 
            font-size: 16px;
        }
        .upload-form button:hover { background: #cc0000; }
        .upload-form button:disabled { background: #cccccc; cursor: not-allowed; }
        
        /* Video Grid */
        .videos-section { flex: 2; }
        .videos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
        .video-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 0 10px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .video-card:hover { transform: translateY(-5px); }
        .video-thumbnail { width: 100%; height: 180px; background: #333; position: relative; overflow: hidden; }
        .video-thumbnail img { width: 100%; height: 100%; object-fit: cover; }
        .video-info { padding: 15px; }
        .video-title { font-weight: bold; margin: 0 0 10px 0; font-size: 18px; color: #333; }
        .video-description { color: #666; margin-bottom: 10px; font-size: 14px; line-height: 1.4; }
        .video-meta { color: #888; font-size: 13px; margin-bottom: 10px; }
        .video-actions { display: flex; align-items: center; justify-content: space-between; }
        .like-btn { 
            background: #ff4444; 
            color: white; 
            border: none; 
            padding: 8px 15px; 
            border-radius: 5px; 
            cursor: pointer; 
            display: flex; 
            align-items: center; 
            gap: 5px;
        }
        .like-btn:hover { background: #dd3333; }
        .like-btn.liked { background: #cc0000; }
        .like-btn i { font-size: 14px; }
        .likes-count { color: #666; font-size: 14px; }
        
        /* Upload Progress */
        #uploadProgress { width: 100%; margin: 10px 0; display: none; }
        #uploadStatus { text-align: center; margin-top: 10px; min-height: 24px; font-size: 14px; }
        .success { color: green; }
        .error { color: red; }
        
        /* File Input */
        .file-input-container { margin: 10px 0; }
        .file-label { 
            display: inline-block; 
            background: #f0f0f0; 
            padding: 10px 15px; 
            border-radius: 5px; 
            cursor: pointer; 
            border: 1px solid #ddd;
            width: 100%;
            text-align: center;
        }
        .file-label:hover { background: #e0e0e0; }
        input[type="file"] { display: none; }
        #fileName { margin-top: 5px; font-size: 13px; color: #666; }
        
        /* Search */
        .search-container { margin-bottom: 20px; }
        .search-input { 
            width: 100%; 
            padding: 12px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            font-size: 16px;
        }
        
        /* No videos message */
        .no-videos { 
            text-align: center; 
            padding: 40px; 
            color: #666; 
            font-size: 18px;
            background: white;
            border-radius: 10px;
            grid-column: 1 / -1;
        }
        
        @media (max-width: 768px) {
            .container { flex-direction: column; }
            .upload-section { max-width: 100%; }
            .videos-grid { grid-template-columns: 1fr; }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <header>
        <div class="logo">VideoHosting</div>
        <div class="user-info">
            Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>
    
    <div class="container">
        <!-- Upload Section -->
        <section class="upload-section">
            <h2><i class="fas fa-upload"></i> Upload Video</h2>
            <form class="upload-form" id="uploadForm" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Video Title" required maxlength="255">
                <textarea name="description" placeholder="Description (optional)" rows="3" maxlength="1000"></textarea>
                
                <div class="file-input-container">
                    <label for="videoFile" class="file-label">
                        <i class="fas fa-video"></i> Choose Video File
                    </label>
                    <input type="file" name="video" id="videoFile" accept="video/*" required>
                    <div id="fileName">No file chosen</div>
                </div>
                
                <div class="file-info">
                    <small>Max file size: 100MB | Allowed: MP4, AVI, MOV, WMV</small>
                </div>
                
                <button type="submit" id="uploadButton">
                    <i class="fas fa-upload"></i> Upload Video
                </button>
                
                <progress id="uploadProgress" value="0" max="100"></progress>
                <div id="uploadStatus"></div>
            </form>
        </section>
        
        <!-- Videos Section -->
        <section class="videos-section">
            <div class="search-container">
                <input type="text" id="searchInput" class="search-input" placeholder="Search videos...">
            </div>
            
            <h2><i class="fas fa-play-circle"></i> All Videos</h2>
            
            <div class="videos-grid" id="videosGrid">
                <?php if (empty($videos)): ?>
                    <div class="no-videos">
                        <i class="fas fa-video-slash fa-3x" style="margin-bottom: 20px;"></i>
                        <p>No videos uploaded yet. Be the first to upload!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($videos as $video): ?>
                    <div class="video-card" data-video-id="<?php echo $video['id']; ?>">
                        <div class="video-thumbnail">
                            <?php if($video['thumbnail'] && file_exists('uploads/thumbnails/' . $video['thumbnail'])): ?>
                                <img src="uploads/thumbnails/<?php echo $video['thumbnail']; ?>" alt="<?php echo htmlspecialchars($video['title']); ?>">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white;">
                                    <i class="fas fa-video fa-3x"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="video-info">
                            <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                            <?php if(!empty($video['description'])): ?>
                                <p class="video-description"><?php echo htmlspecialchars(substr($video['description'], 0, 100)) . (strlen($video['description']) > 100 ? '...' : ''); ?></p>
                            <?php endif; ?>
                            <div class="video-meta">
                                <i class="fas fa-user"></i> <?php echo htmlspecialchars($video['username']); ?> | 
                                <i class="fas fa-eye"></i> <?php echo $video['views']; ?> views | 
                                <i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($video['uploaded_at'])); ?>
                            </div>
                            <div class="video-actions">
                                <button class="like-btn <?php echo $video['user_liked'] ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(<?php echo $video['id']; ?>)">
                                    <i class="fas fa-heart"></i>
                                    <?php echo $video['user_liked'] ? 'Unlike' : 'Like'; ?>
                                </button>
                                <span class="likes-count">
                                    <i class="fas fa-thumbs-up"></i> <?php echo $video['like_count']; ?> likes
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <script>
        // Показываем имя выбранного файла
        document.getElementById('videoFile').addEventListener('change', function(e) {
            const file = this.files[0];
            const fileNameDiv = document.getElementById('fileName');
            const fileInfo = document.querySelector('.file-info small');
            
            if (file) {
                fileNameDiv.textContent = file.name;
                const fileSize = (file.size / (1024 * 1024)).toFixed(2);
                fileInfo.textContent = `File: ${file.name} (${fileSize} MB) | Max: 100MB`;
            } else {
                fileNameDiv.textContent = 'No file chosen';
                fileInfo.textContent = 'Max file size: 100MB | Allowed: MP4, AVI, MOV, WMV';
            }
        });
        
        // AJAX загрузка видео (простая версия)
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const progressBar = document.getElementById('uploadProgress');
            const statusDiv = document.getElementById('uploadStatus');
            const uploadButton = document.getElementById('uploadButton');
            
            // Валидация
            const videoFile = document.getElementById('videoFile').files[0];
            if (!videoFile) {
                statusDiv.innerHTML = '<span class="error">Please select a video file</span>';
                return;
            }
            
            // Проверка размера (100MB)
            if (videoFile.size > 100 * 1024 * 1024) {
                statusDiv.innerHTML = '<span class="error">File is too large (max 100MB)</span>';
                return;
            }
            
            // Проверка типа
            const allowedTypes = ['video/mp4', 'video/avi', 'video/quicktime', 'video/x-ms-wmv'];
            if (!allowedTypes.includes(videoFile.type)) {
                statusDiv.innerHTML = '<span class="error">Invalid file type. Allowed: MP4, AVI, MOV, WMV</span>';
                return;
            }
            
            // Настройка UI
            progressBar.style.display = 'block';
            progressBar.value = 0;
            statusDiv.innerHTML = '<span style="color: blue;">Starting upload...</span>';
            uploadButton.disabled = true;
            uploadButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
            
            // Создаем XMLHttpRequest для отслеживания прогресса
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percent = Math.round((e.loaded / e.total) * 100);
                    progressBar.value = percent;
                    statusDiv.innerHTML = `<span style="color: blue;">Uploading: ${percent}%</span>`;
                }
            });
            
            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            progressBar.value = 100;
                            statusDiv.innerHTML = '<span class="success">✓ ' + response.message + '</span>';
                            
                            // Обновляем страницу через 2 секунды
                            setTimeout(() => {
                                location.reload();
                            }, 2000);
                        } else {
                            statusDiv.innerHTML = '<span class="error">✗ ' + response.message + '</span>';
                            progressBar.style.display = 'none';
                            uploadButton.disabled = false;
                            uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Video';
                        }
                    } catch (error) {
                        statusDiv.innerHTML = '<span class="error">✗ Invalid server response</span>';
                        console.error('Parse error:', error);
                        progressBar.style.display = 'none';
                        uploadButton.disabled = false;
                        uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Video';
                    }
                } else {
                    statusDiv.innerHTML = '<span class="error">✗ Server error: ' + xhr.status + '</span>';
                    progressBar.style.display = 'none';
                    uploadButton.disabled = false;
                    uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Video';
                }
            };
            
            xhr.onerror = function() {
                statusDiv.innerHTML = '<span class="error">✗ Network error. Please try again.</span>';
                progressBar.style.display = 'none';
                uploadButton.disabled = false;
                uploadButton.innerHTML = '<i class="fas fa-upload"></i> Upload Video';
            };
            
            // Отправляем запрос
            xhr.open('POST', 'upload.php');
            xhr.send(formData);
        });
        
        // Поиск видео
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = this.value.toLowerCase();
            const videoCards = document.querySelectorAll('.video-card');
            
            videoCards.forEach(card => {
                const title = card.querySelector('.video-title').textContent.toLowerCase();
                const description = card.querySelector('.video-description')?.textContent.toLowerCase() || '';
                
                if (title.includes(searchTerm) || description.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
        // Функция для лайков
        function toggleLike(videoId) {
            const likeBtn = event.target.closest('.like-btn');
            const likesCount = likeBtn.parentElement.querySelector('.likes-count');
            
            // Отправляем запрос на сервер
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
                    // Обновляем кнопку
                    if (data.action === 'liked') {
                        likeBtn.innerHTML = '<i class="fas fa-heart"></i> Unlike';
                        likeBtn.classList.add('liked');
                    } else {
                        likeBtn.innerHTML = '<i class="fas fa-heart"></i> Like';
                        likeBtn.classList.remove('liked');
                    }
                    
                    // Обновляем счетчик
                    likesCount.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.likes_count} likes`;
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Network error. Please try again.');
            });
        }
    </script>
</body>
</html>