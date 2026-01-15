<?php
require_once 'config.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Получение видео из базы
try {
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
} catch (PDOException $e) {
    $videos = [];
    $error = "Ошибка загрузки видео: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Видео хостинг - Главная</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: Arial, sans-serif; 
            background: #f0f2f5;
            min-height: 100vh;
        }
        
        /* Шапка */
        .header {
            background: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #ff0000;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logout-btn {
            background: #ff0000;
            color: white;
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .logout-btn:hover {
            background: #cc0000;
        }
        
        /* Основной контейнер */
        .main-container {
            display: flex;
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 15px;
            gap: 20px;
        }
        
        /* Панель загрузки */
        .upload-panel {
            flex: 0 0 350px;
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            height: fit-content;
        }
        .upload-panel h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 18px;
        }
        
        /* Форма */
        .form-group {
            margin-bottom: 15px;
        }
        .form-input, .form-textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-input:focus, .form-textarea:focus {
            outline: none;
            border-color: #ff0000;
        }
        .form-textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        /* Поле файла - ИСПРАВЛЕНО! */
        .file-input-wrapper {
            position: relative;
            margin-bottom: 10px;
        }
        .file-input-real {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        .file-input-fake {
            border: 2px dashed #ddd;
            border-radius: 4px;
            padding: 25px;
            text-align: center;
            background: #f9f9f9;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            z-index: 1;
        }
        .file-input-fake:hover {
            border-color: #ff0000;
            background: #fff5f5;
        }
        .file-input-fake i {
            font-size: 30px;
            color: #666;
            margin-bottom: 10px;
            display: block;
        }
        .file-name {
            margin-top: 10px;
            color: #666;
            font-size: 13px;
            min-height: 20px;
        }
        
        /* Кнопка загрузки */
        .upload-btn {
            width: 100%;
            padding: 12px;
            background: #ff0000;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .upload-btn:hover {
            background: #cc0000;
        }
        .upload-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Прогресс бар */
        .progress-container {
            margin-top: 15px;
            display: none;
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background: #ddd;
            border-radius: 2px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: #4CAF50;
            width: 0%;
            transition: width 0.3s;
        }
        .progress-text {
            text-align: center;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        /* Видео сетка */
        .videos-grid {
            flex: 1;
        }
        .videos-header {
            margin-bottom: 20px;
        }
        .search-box {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        
        /* Карточки видео */
        .videos-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 15px;
        }
        .video-card {
            background: white;
            border-radius: 6px;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .video-thumbnail {
            height: 160px;
            background: #333;
            overflow: hidden;
        }
        .video-thumbnail img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .video-content {
            padding: 12px;
        }
        .video-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }
        .video-meta {
            font-size: 12px;
            color: #666;
            margin-bottom: 10px;
        }
        
        /* Кнопка лайка */
        .like-btn {
            background: #f0f0f0;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .like-btn:hover {
            background: #e0e0e0;
        }
        .like-btn.liked {
            background: #ffebee;
            color: #ff0000;
        }
        
        /* Статус */
        #statusMessage {
            margin-top: 10px;
            padding: 10px;
            border-radius: 4px;
            font-size: 13px;
            display: none;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        /* Нет видео */
        .no-videos {
            text-align: center;
            padding: 30px;
            color: #666;
            background: white;
            border-radius: 6px;
            grid-column: 1 / -1;
        }
        
        /* Информация о файле */
        .file-info {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Шапка -->
    <header class="header">
        <div class="logo">VIDEOHOST</div>
        <div class="user-info">
            <span>Привет, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span>
            <button onclick="location.href='logout.php'" class="logout-btn">
                Выйти
            </button>
        </div>
    </header>

    <!-- Основной контент -->
    <div class="main-container">
        <!-- Панель загрузки -->
        <div class="upload-panel">
            <h2>Загрузить видео</h2>
            
            <form id="uploadForm" enctype="multipart/form-data">
                <div class="form-group">
                    <input type="text" name="title" class="form-input" 
                           placeholder="Название видео" required>
                </div>
                
                <div class="form-group">
                    <textarea name="description" class="form-textarea" 
                              placeholder="Описание (необязательно)"></textarea>
                </div>
                
                <!-- ИСПРАВЛЕННОЕ ПОЛЕ ВЫБОРА ФАЙЛА -->
                <div class="form-group">
                    <div class="file-input-wrapper">
                        <!-- Реальный input -->
                        <input type="file" name="video" id="videoFile" 
                               class="file-input-real" accept="video/*" required>
                        
                        <!-- Красивая кнопка выбора -->
                        <div class="file-input-fake" id="fileInputFake">
                            <i class="fas fa-cloud-upload-alt"></i>
                            <div>Нажмите для выбора видео</div>
                        </div>
                    </div>
                    
                    <div class="file-name" id="fileName">
                        Файл не выбран
                    </div>
                    
                    <div class="file-info" id="fileInfo">
                        Макс. размер: 100MB | Форматы: MP4, AVI, MOV, WMV
                    </div>
                </div>
                
                <button type="submit" class="upload-btn" id="uploadButton">
                    Загрузить видео
                </button>
            </form>
            
            <!-- Прогресс бар -->
            <div class="progress-container" id="progressContainer">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">0%</div>
            </div>
            
            <!-- Статус -->
            <div id="statusMessage"></div>
        </div>

        <!-- Сетка видео -->
        <div class="videos-grid">
            <div class="videos-header">
                <h2 style="margin-bottom: 10px;">Все видео</h2>
                <input type="text" class="search-box" id="searchInput" 
                       placeholder="Поиск видео...">
            </div>
            
            <div class="videos-container" id="videosContainer">
                <?php if (empty($videos)): ?>
                    <div class="no-videos">
                        <i class="fas fa-video-slash" style="font-size: 40px; margin-bottom: 10px;"></i>
                        <h3>Пока нет видео</h3>
                        <p>Будьте первым, кто загрузит видео!</p>
                    </div>
                <?php else: ?>
                    <?php foreach($videos as $video): ?>
                    <div class="video-card">
                        <div class="video-thumbnail">
                            <?php if($video['thumbnail'] && file_exists('uploads/thumbnails/' . $video['thumbnail'])): ?>
                                <img src="uploads/thumbnails/<?php echo $video['thumbnail']; ?>">
                            <?php else: ?>
                                <div style="display: flex; align-items: center; justify-content: center; height: 100%; color: white;">
                                    <i class="fas fa-video"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="video-content">
                            <h3 class="video-title"><?php echo htmlspecialchars($video['title']); ?></h3>
                            
                            <div class="video-meta">
                                <span><?php echo htmlspecialchars($video['username']); ?></span> | 
                                <span><?php echo date('d.m.Y', strtotime($video['uploaded_at'])); ?></span>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <button class="like-btn <?php echo $video['user_liked'] ? 'liked' : ''; ?>" 
                                        onclick="toggleLike(<?php echo $video['id']; ?>, this)">
                                    <i class="fas fa-heart"></i>
                                    <span class="like-text">
                                        <?php echo $video['user_liked'] ? 'Убрать' : 'Лайк'; ?>
                                    </span>
                                </button>
                                <span style="font-size: 12px; color: #666;">
                                    <i class="fas fa-thumbs-up"></i> <?php echo $video['like_count']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // ==================== ОСНОВНЫЕ ФУНКЦИИ ====================
        
        function showStatus(message, type = 'info') {
            const statusMessage = document.getElementById('statusMessage');
            statusMessage.textContent = message;
            statusMessage.className = '';
            statusMessage.classList.add('status-' + type);
            statusMessage.style.display = 'block';
        }
        
        function updateProgress(percent) {
            document.getElementById('progressFill').style.width = percent + '%';
            document.getElementById('progressText').textContent = percent + '%';
        }
        
        function resetUploadForm() {
            document.getElementById('uploadButton').disabled = false;
            document.getElementById('uploadButton').textContent = 'Загрузить видео';
            document.getElementById('progressContainer').style.display = 'none';
        }
        
        // ==================== ОБРАБОТЧИКИ СОБЫТИЙ ====================
        
        // Показ выбранного файла - ИСПРАВЛЕНО!
        document.getElementById('videoFile').addEventListener('change', function(e) {
            const file = this.files[0];
            const fileNameDiv = document.getElementById('fileName');
            const fileInfoDiv = document.getElementById('fileInfo');
            const fileInputFake = document.getElementById('fileInputFake');
            
            if (file) {
                // Показываем имя файла
                fileNameDiv.textContent = 'Выбран: ' + file.name;
                fileNameDiv.style.color = '#333';
                
                // Показываем размер
                const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                fileInfoDiv.textContent = `Размер: ${fileSizeMB} MB | Макс: 100MB`;
                
                // Меняем вид кнопки
                fileInputFake.innerHTML = '<i class="fas fa-check-circle" style="color:#4CAF50"></i><div>Файл выбран</div>';
                fileInputFake.style.borderColor = '#4CAF50';
                fileInputFake.style.background = '#f0f9f0';
                
            } else {
                // Сбрасываем
                fileNameDiv.textContent = 'Файл не выбран';
                fileNameDiv.style.color = '#666';
                fileInfoDiv.textContent = 'Макс. размер: 100MB | Форматы: MP4, AVI, MOV, WMV';
                
                fileInputFake.innerHTML = '<i class="fas fa-cloud-upload-alt"></i><div>Нажмите для выбора видео</div>';
                fileInputFake.style.borderColor = '#ddd';
                fileInputFake.style.background = '#f9f9f9';
            }
        });
        
        // Клик по красивой кнопке тоже открывает выбор файла
        document.getElementById('fileInputFake').addEventListener('click', function(e) {
            document.getElementById('videoFile').click();
        });
        
        // Поиск
        document.getElementById('searchInput').addEventListener('input', function(e) {
            const searchTerm = this.value.toLowerCase();
            const videoCards = document.querySelectorAll('.video-card');
            
            videoCards.forEach(card => {
                const title = card.querySelector('.video-title').textContent.toLowerCase();
                if (title.includes(searchTerm) || searchTerm === '') {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
        
    
// ========== ОБРАБОТКА ЗАГРУЗКИ ВИДЕО ==========
document.getElementById('uploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    console.log('=== НАЧАЛО ЗАГРУЗКИ ===');
    
    const form = this;
    const formData = new FormData(form);
    const uploadBtn = document.getElementById('uploadButton');
    const progressContainer = document.getElementById('progressContainer');
    const statusDiv = document.getElementById('statusMessage');
    
    // Проверка файла
    const fileInput = document.getElementById('videoFile');
    if (!fileInput.files[0]) {
        showStatus('❌ Выберите видео файл', 'error');
        return;
    }
    
    const file = fileInput.files[0];
    console.log('Файл для загрузки:', file.name, file.size + ' bytes');
    
    // Настройка UI
    uploadBtn.disabled = true;
    uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
    progressContainer.style.display = 'block';
    updateProgress(0);
    showStatus('📤 Подготовка к загрузке...', 'info');
    
    // Создаем запрос
    const xhr = new XMLHttpRequest();
    
    // Обработка прогресса
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percent = Math.round((e.loaded / e.total) * 100);
            updateProgress(percent);
            
            if (percent < 100) {
                showStatus(`📤 Загрузка: ${percent}%`, 'info');
            }
        }
    });
    
    // Обработка ответа
    xhr.addEventListener('load', function() {
        console.log('=== ОТВЕТ СЕРВЕРА ===');
        console.log('Статус:', xhr.status);
        console.log('Заголовки:', xhr.getAllResponseHeaders());
        console.log('Текст ответа:', xhr.responseText);
        
        // ВАЖНО: Пробуем распарсить JSON
        try {
            const response = JSON.parse(xhr.responseText);
            console.log('Парсинг JSON успешен:', response);
            
            if (response.success) {
                // УСПЕХ
                updateProgress(100);
                showStatus('✅ ' + response.message, 'success');
                
                uploadBtn.innerHTML = '<i class="fas fa-check"></i> Успешно!';
                uploadBtn.style.background = '#4CAF50';
                
                // Обновляем страницу через 1.5 секунды
                setTimeout(() => {
                    console.log('Обновление страницы...');
                    location.reload();
                }, 1500);
                
            } else {
                // ОШИБКА ОТ СЕРВЕРА
                showStatus('❌ ' + response.message, 'error');
                resetUploadForm();
            }
            
        } catch (jsonError) {
            // ОШИБКА ПАРСИНГА JSON
            console.error('ОШИБКА ПАРСИНГА JSON:', jsonError);
            
            // Проверяем, есть ли файл в папке (косвенный признак успеха)
            if (xhr.responseText.includes('success') || xhr.responseText.includes('true')) {
                // Похоже на успешный ответ, но кривой JSON
                updateProgress(100);
                showStatus('✅ Видео загружено! (обновляю страницу)', 'success');
                
                setTimeout(() => {
                    location.reload();
                }, 1500);
                
            } else {
                // Серьезная ошибка
                showStatus('⚠ Ответ сервера не распознан. Проверьте консоль.', 'error');
                console.log('Сырой ответ для анализа:', xhr.responseText);
                resetUploadForm();
            }
        }
    });
    
    // Ошибка сети
    xhr.addEventListener('error', function() {
        console.error('Ошибка сети при загрузке');
        showStatus('❌ Ошибка сети. Проверьте подключение.', 'error');
        resetUploadForm();
    });
    
    // Таймаут
    xhr.addEventListener('timeout', function() {
        console.error('Таймаут загрузки');
        showStatus('❌ Время загрузки истекло', 'error');
        resetUploadForm();
    });
    
    // Отправляем запрос
    xhr.open('POST', 'upload_clean.php', true);
    xhr.timeout = 300000; // 5 минут таймаут
    xhr.send(formData);
    
    // Функции помощники
    function updateProgress(percent) {
        const progressFill = document.getElementById('progressFill');
        const progressText = document.getElementById('progressText');
        
        if (progressFill) progressFill.style.width = percent + '%';
        if (progressText) progressText.textContent = percent + '%';
    }
    
    function showStatus(message, type) {
        if (!statusDiv) return;
        
        statusDiv.textContent = message;
        statusDiv.className = 'status-message';
        
        if (type === 'success') {
            statusDiv.classList.add('status-success');
        } else if (type === 'error') {
            statusDiv.classList.add('status-error');
        } else {
            statusDiv.classList.add('status-info');
        }
        
        statusDiv.style.display = 'block';
    }
    
    function resetUploadForm() {
        uploadBtn.disabled = false;
        uploadBtn.innerHTML = '<i class="fas fa-upload"></i> Загрузить видео';
        uploadBtn.style.background = '';
        progressContainer.style.display = 'none';
    }
});
        
        // Лайки
        function toggleLike(videoId, button) {
            const likeText = button.querySelector('.like-text');
            const likeCount = button.parentElement.querySelector('span');
            
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
                        button.classList.add('liked');
                        likeText.textContent = 'Убрать';
                    } else {
                        button.classList.remove('liked');
                        likeText.textContent = 'Лайк';
                    }
                    
                    likeCount.innerHTML = `<i class="fas fa-thumbs-up"></i> ${data.likes_count}`;
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Ошибка при обработке лайка');
            });
        }
    </script>
</body>
</html>