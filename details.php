<?php
include 'auth_check.php'; // Запрос логина и пароля
// Database Connection
$servername = "drbgz515.mysql.network:10501";
$username = "gym_admin";
$password = "jALub29P75";
$dbname = "gym_asisstant_clients";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get client ID from URL
$client_id = isset($_GET['tg_id']) ? (int)$_GET['tg_id'] : 0;

// Handle form submission (saving edits)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_changes'])) {
    $new_full_name = $conn->real_escape_string($_POST['full_name']);
    $new_age = (int)$_POST['age'];
    $new_height = (int)$_POST['height'];
    $new_weight = (int)$_POST['weight'];
    $new_gender = $conn->real_escape_string($_POST['gender']);
    $new_additional_info = $conn->real_escape_string($_POST['additional_info']);

    // Fetch and decode existing personal_info
    $sql_fetch = "SELECT personal_info FROM user_info WHERE tg_id = $client_id";
    $result_fetch = $conn->query($sql_fetch);
    $row_fetch = $result_fetch->fetch_assoc();
    $personal_info = !empty($row_fetch['personal_info']) ? json_decode($row_fetch['personal_info'], true) : [];

    // Update values
    $personal_info['full_name'] = $new_full_name;
    $personal_info['age'] = $new_age;
    $personal_info['height'] = $new_height;
    $personal_info['weight'] = $new_weight;
    $personal_info['sex'] = $new_gender;
    $personal_info['additional_info'] = $new_additional_info;

    // Encode back to JSON and update database
    $updated_personal_info = json_encode($personal_info, JSON_UNESCAPED_UNICODE);
    $sql_update = "UPDATE user_info SET personal_info = '$updated_personal_info' WHERE tg_id = $client_id";

    if ($conn->query($sql_update) === TRUE) {
        header("Location: details.php?tg_id=$client_id&updated=true");
        exit();
    } else {
        echo "<p>Ошибка обновления: " . $conn->error . "</p>";
    }
}

// Fetch user data
$sql = "SELECT * FROM user_info WHERE tg_id = $client_id";
$result = $conn->query($sql);
$client = $result->fetch_assoc();

// Check if client exists
if (!$client) {
    echo "<p>Клиент не найден.</p>";
    exit;
}

// Decode `personal_info`
$personal_info = !empty($client['personal_info']) ? json_decode($client['personal_info'], true) : [];

// Extract personal details
$full_name = $personal_info['full_name'] ?? 'Нет имени';
$age = $personal_info['age'] ?? 'Не указан';
$height = $personal_info['height'] ?? 'Не указан';
$weight = $personal_info['weight'] ?? 'Не указан';
$gender = $personal_info['sex'] ?? 'Не указан';
$additional_info = $personal_info['additional_info'] ?? 'Нет дополнительной информации';

// Decode `nutritional_info`
$nutrition_data = !empty($client['nutritional_info']) ? json_decode($client['nutritional_info'], true) : [];

// Fetch recommendations
$recommendations = !empty($client['recommendations']) ? json_decode($client['recommendations'], true) : [];
$nutrition_recommendation = $recommendations['nutrition_recommendation'] ?? '';
$training_recommendation = $recommendations['training_recommendation'] ?? '';

// Handle recommendation save
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_recommendations'])) {
    $nutrition_recommendation = $conn->real_escape_string($_POST['nutrition_recommendation']);
    $training_recommendation = $conn->real_escape_string($_POST['training_recommendation']);

    $recommendations_json = json_encode([
        "nutrition_recommendation" => $nutrition_recommendation,
        "training_recommendation" => $training_recommendation
    ], JSON_UNESCAPED_UNICODE);

    $sql_update_recommendations = "UPDATE user_info SET recommendations = '$recommendations_json' WHERE tg_id = $client_id";
    
    if ($conn->query($sql_update_recommendations) === TRUE) {
        header("Location: details.php?tg_id=$client_id&updated=true");
        exit();
    } else {
        echo "<p>Ошибка обновления рекомендаций: " . $conn->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали клиента</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="icon.png">
    <script>
        function toggleEditMode() {
            document.getElementById('editMode').style.display = 'block';
            document.getElementById('viewMode').style.display = 'none';
        }

        function cancelEdit() {
            document.getElementById('editMode').style.display = 'none';
            document.getElementById('viewMode').style.display = 'block';
        }
    </script>
</head>
<body>
    <div class="container">
        <h3 class="animated-text"><span></span></h2>
        <?php if (isset($_GET['updated'])): ?>
            <div id="notification" class="notification">✅ Данные успешно обновлены!</div>
        <?php endif; ?>

        <!-- Режим просмотра -->
        <div id="viewMode" class="info-card">
            <p><strong>Имя:</strong> <?php echo htmlspecialchars($full_name); ?></p>
            <p><strong>Возраст:</strong> <?php echo htmlspecialchars($age); ?> лет</p>
            <p><strong>Рост:</strong> <?php echo htmlspecialchars($height); ?> см</p>
            <p><strong>Вес:</strong> <?php echo htmlspecialchars($weight); ?> кг</p>
            <p><strong>Пол:</strong> <?php echo htmlspecialchars($gender); ?></p>
            <p><strong>Доп. информация:</strong> <?php echo htmlspecialchars($additional_info); ?></p>
            <button class="btn edit-btn" onclick="toggleEditMode()">✏️ Редактировать</button>
        </div>

        <!-- Режим редактирования -->
        <div id="editMode" class="edit-card" style="display: none;">
            <h3>Редактирование</h3>
            <form method="POST">
                <label>Имя:</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>

                <label>Возраст:</label>
                <input type="number" name="age" value="<?php echo htmlspecialchars($age); ?>" required>

                <label>Рост:</label>
                <input type="number" name="height" value="<?php echo htmlspecialchars($height); ?>" required>

                <label>Вес:</label>
                <input type="number" name="weight" value="<?php echo htmlspecialchars($weight); ?>" required>

                <label>Пол:</label>
                <select name="gender" required>
                    <option value="male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>Мужской</option>
                    <option value="female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>Женский</option>
                </select>

                <label>Доп. информация:</label>
                <input type="text" name="additional_info" value="<?php echo htmlspecialchars($additional_info); ?>">

                <div class="form-actions">
                    <button type="submit" name="save_changes" class="btn save-btn">💾 Сохранить</button>
                    <button type="button" class="btn cancel-btn" onclick="cancelEdit()">❌ Отмена</button>
                </div>
            </form>
        </div>

        <!-- Данные по питанию -->
        <h3 class="animated-text2"><span></span></h3>
        <table border="1">
            <tr>
                <th>Дата</th>
                <th>Блюдо</th>
                <th>Протеины</th>
                <th>Жиры</th>
                <th>Углеводы</th>
                <th>Калорийность</th>
            </tr>

            <?php
            if (!empty($nutrition_data) && is_array($nutrition_data)) {
                foreach ($nutrition_data as $date => $foods) {
                    if (is_array($foods)) {
                        foreach ($foods as $food) { ?>
                            <tr>
                                <td><?php echo htmlspecialchars($date); ?></td>
                                <td><?php echo htmlspecialchars($food['food_item'] ?? 'Нет данных'); ?></td>
                                <td><?php echo htmlspecialchars($food['proteins'] ?? 0); ?> г</td>
                                <td><?php echo htmlspecialchars($food['fats'] ?? 0); ?> г</td>
                                <td><?php echo htmlspecialchars($food['carbohydrates'] ?? 0); ?> г</td>
                                <td><?php echo htmlspecialchars($food['calories'] ?? 0); ?> ккал</td>
                            </tr>
                        <?php }
                    }
                }
            } else {
                echo "<tr><td colspan='6'>Нет данных по питанию</td></tr>";
            }
            ?>
        </table>
        <h3 class="animated-text3"><span></span></h3>
        <form method="POST">
            <label>Рекомендации по питанию:</label>
            <textarea name="nutrition_recommendation" rows="4"><?php echo htmlspecialchars($nutrition_recommendation); ?></textarea>

            <label>Рекомендации по тренировкам:</label>
            <textarea name="training_recommendation" rows="4"><?php echo htmlspecialchars($training_recommendation); ?></textarea>

            <button type="submit" name="save_recommendations" class="btn save-btn">💾 Сохранить рекомендации</button>
        </form>

        <!-- Back Button -->
        <a href="index.php" class="button back-button">⬅ Вернуться к списку</a>
    </div>
    <script>
window.onload = function() {
    if (document.getElementById('notification')) {
        var notification = document.getElementById('notification');
        notification.classList.add('show');
        setTimeout(function() {
            notification.style.display = 'none';
        }, 6000); // Уведомление исчезнет через 6 секунд
    }
};
</script>
</body>
</html>

<?php
$conn->close();
?>