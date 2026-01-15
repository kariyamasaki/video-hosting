<?php
// check_json.php - проверка что сервер возвращает чистый JSON

// Попробуйте загрузить видео через эту форму
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Очистим весь предыдущий вывод
    ob_clean();
    
    // Простой тестовый ответ
    $test_response = [
        'success' => true,
        'message' => 'Тестовое сообщение',
        'test' => 'работает'
    ];
    
    // Отправляем чистый JSON
    header('Content-Type: application/json');
    echo json_encode($test_response);
    exit;
}
?>
<!DOCTYPE html>
<html>
<body>
    <h2>Тест JSON ответа</h2>
    <form method="POST">
        <button type="submit">Тестировать JSON</button>
    </form>
    
    <script>
        document.querySelector('form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const response = await fetch('check_json.php', {
                method: 'POST'
            });
            
            const text = await response.text();
            console.log('Сырой ответ:', text);
            
            try {
                const data = JSON.parse(text);
                console.log('Парсинг успешен:', data);
                alert('JSON работает! Проверьте консоль');
            } catch (error) {
                console.error('Ошибка парсинга:', error);
                alert('Ошибка JSON: ' + text.substring(0, 100));
            }
        });
    </script>
</body>
</html>