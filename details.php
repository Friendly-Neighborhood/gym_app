<?php
include 'auth_check.php'; // Запрос логина и пароля

if (!isset($_GET['tg_id']) || empty($_GET['tg_id'])) {
    // Если нет, редиректим на главную страницу
    header("Location: index.php");
    exit();
}
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
$additional_info = $personal_info['additional_info'];

// Decode `nutritional_info`
$nutrition_data = !empty($client['nutritional_info']) ? json_decode($client['nutritional_info'], true) : [];

// Fetch recommendations
$recommendations = !empty($client['recommendations']) ? json_decode($client['recommendations'], true) : [];
$nutrition_recommendation = $recommendations['nutrition_recommendation'] ?? '';
$training_recommendation = $recommendations['training_recommendation'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_nutrition_recommendations'])) {
    $new_aim = $conn->real_escape_string($_POST['aim']);
    $new_basal = (int)$_POST['metabolism_basal'];
    $new_maintenance = (int)$_POST['metabolism_maintenance'];
    $new_bulking = (int)$_POST['metabolism_bulking'];
    $new_cutting = (int)$_POST['metabolism_cutting'];

    // Преобразуем БЖУ в decimal с одной цифрой после запятой
    $new_proteins = isset($_POST['proteins']) ? number_format((float)$_POST['proteins'], 1, '.', '') : "0.0";
    $new_fats = isset($_POST['fats']) ? number_format((float)$_POST['fats'], 1, '.', '') : "0.0";
    $new_carbohydrates = isset($_POST['carbohydrates']) ? number_format((float)$_POST['carbohydrates'], 1, '.', '') : "0.0";

    $new_other_recommendations = $conn->real_escape_string($_POST['other_recommendations']);
    $new_training_recommendations = $conn->real_escape_string($_POST['training_recommendations']);

    // Обновленная JSON-структура
    $updated_nutrition_data = json_encode([
        "training_recommendation" => $new_training_recommendations,
        "nutrition_recommendation" => [
            "aim" => $new_aim,
            "metabolism" => [
                "basal" => $new_basal,
                "maintenance" => $new_maintenance,
                "bulking" => $new_bulking,
                "cutting" => $new_cutting
            ],
            "nutrients_per_kg" => [
                "proteins" => $new_proteins,
                "fats" => $new_fats,
                "carbohydrates" => $new_carbohydrates
            ],
            "other_recommendations" => $new_other_recommendations
        ]
    ], JSON_UNESCAPED_UNICODE);

    // Запрос на обновление в БД
    $sql_update_nutrition = "UPDATE user_info SET recommendations = '$updated_nutrition_data' WHERE tg_id = $client_id";

    if ($conn->query($sql_update_nutrition) === TRUE) {
        header("Location: details.php?tg_id=$client_id&updated=true");
        exit();
    } else {
        echo "<p>Ошибка обновления: " . $conn->error . "</p>";
    }
}

// Форма обработки добавления новой тренировки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_training'])) {
    // Получаем переданные данные
    $selected_muscles = isset($_POST['muscle_group']) ? json_decode($_POST['muscle_group'], true) : [];

    // Проверяем, что список не пустой
    if (!empty($selected_muscles)) {
        // Получаем текущие тренировки из БД
        $sql_fetch = "SELECT trainings FROM user_info WHERE tg_id = $client_id";
        $result_fetch = $conn->query($sql_fetch);
        $row_fetch = $result_fetch->fetch_assoc();
        $existing_trainings = !empty($row_fetch['trainings']) ? json_decode($row_fetch['trainings'], true) : [];

        // Создаём новую тренировку
        $new_training = [
            "date" => date("Y-m-d"),
            "muscle_group" => $selected_muscles
        ];

        // Добавляем новую тренировку **в начало** массива
        array_unshift($existing_trainings, $new_training);

        // Обновляем БД
        $updated_trainings_json = json_encode($existing_trainings, JSON_UNESCAPED_UNICODE);
        $sql_update = "UPDATE user_info SET trainings = '$updated_trainings_json' WHERE tg_id = $client_id";

        if ($conn->query($sql_update) === TRUE) {
            // Отправляем данные в API
            sendTrainingDataToAPI($client_id, $new_training);

            // Перенаправляем с уведомлением
            header("Location: details.php?tg_id=$client_id&training_added=true");
            exit();
        } else {
            echo "<p>Ошибка сохранения тренировки: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Выберите хотя бы одну группу мышц!</p>";
    }
}

function sendTrainingDataToAPI($userId, $trainingData) {
    $apiUrl = "http://gym-bot.site:3001/api/training_added";
    
    // Формируем JSON-объект
    $payload = json_encode([
        "userId" => (string)$userId,
        "trainings" => $trainingData
    ], JSON_UNESCAPED_UNICODE);

    // Инициализируем cURL
    $ch = curl_init($apiUrl);

    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Content-Type: application/json"
    ]);

    // Выполняем запрос
    $response = curl_exec($ch);
if ($response === false) {
    error_log("Ошибка cURL: " . curl_error($ch)); // Логируем ошибку
} else {
    error_log("Ответ сервера: " . $response);
}
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    

    curl_close($ch);

    // Логирование ошибки, если запрос не успешен
    if ($httpCode !== 200) {
        error_log("Ошибка отправки тренировки: " . $response);
    }
}

// Получаем текущую дату подписки
$active_till = $client['active_till'] ?? null;

// Проверяем, просрочена ли подписка
$is_expired = (!$active_till || strtotime($active_till) < time()) ? true : false;

// Форма продления подписки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['extend_subscription'])) {
    $new_active_till = $_POST['subscription_date'];

    if (!empty($new_active_till)) {
        $sql_update_subscription = "UPDATE user_info SET active_till = '$new_active_till' WHERE tg_id = $client_id";
    
        if ($conn->query($sql_update_subscription) === TRUE) {
            header("Location: details.php?tg_id=$client_id&subscription_updated=true");
            exit();
        } else {
            echo "<p>Ошибка обновления подписки: " . $conn->error . "</p>";
        }

        // Обновляем подписку в БД
        $sql_update_subscription = "UPDATE user_info SET active_till = '$new_active_till' WHERE tg_id = $client_id";

        if ($conn->query($sql_update_subscription) === TRUE) {
            header("Location: details.php?tg_id=$client_id&subscription_updated=true");
            exit();
        } else {
            echo "<p>Ошибка обновления подписки: " . $conn->error . "</p>";
        }
    }
}

// Обработка удаления записи из таблицы приёмов пищи
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_food'])) {
    $food_date = $conn->real_escape_string($_POST['food_date']);
    $food_name = $conn->real_escape_string($_POST['food_name']);

    // Получаем текущие данные
    $sql_fetch = "SELECT nutritional_info FROM user_info WHERE tg_id = $client_id";
    $result_fetch = $conn->query($sql_fetch);
    $row_fetch = $result_fetch->fetch_assoc();
    $nutrition_data = !empty($row_fetch['nutritional_info']) ? json_decode($row_fetch['nutritional_info'], true) : [];

    // Проверяем, есть ли данные за эту дату
    if (isset($nutrition_data[$food_date])) {
        foreach ($nutrition_data[$food_date] as $index => $food) {
            if ($food['food_item'] === $food_name) {
                unset($nutrition_data[$food_date][$index]);
                if (empty($nutrition_data[$food_date])) {
                    unset($nutrition_data[$food_date]); // Удаляем дату, если нет записей
                }
                break;
            }
        }
    }

    // Если после удаления в `nutritional_info` не осталось данных, записываем NULL
    if (empty($nutrition_data)) {
        $sql_update_nutrition = "UPDATE user_info SET nutritional_info = NULL WHERE tg_id = $client_id";
    } else {
        $updated_nutrition_data = json_encode($nutrition_data, JSON_UNESCAPED_UNICODE);
        $sql_update_nutrition = "UPDATE user_info SET nutritional_info = '$updated_nutrition_data' WHERE tg_id = $client_id";
    }

    if ($conn->query($sql_update_nutrition) === TRUE) {
        // Перенаправляем, добавляя параметр food_deleted для показа уведомления
        header("Location: details.php?tg_id=$client_id&food_deleted=true");
        exit();
    } else {
        echo "<p>Ошибка удаления: " . $conn->error . "</p>";
    }
}

// Отмена подписки (ставим дату подписки на вчера)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_subscription'])) {
    $yesterday = date("Y-m-d H:i:s", strtotime("-1 day"));

    $sql_cancel_subscription = "UPDATE user_info SET active_till = '$yesterday' WHERE tg_id = $client_id";

    if ($conn->query($sql_cancel_subscription) === TRUE) {
        header("Location: details.php?tg_id=$client_id&subscription_cancelled=true");
        exit();
    } else {
        echo "<p>Ошибка отмены подписки: " . $conn->error . "</p>";
    }
}


$vitamin_data = $vitamin_data ?? [];
$nutrition_data = $nutrition_data ?? []; // Если null, заменяем на []
?>
<!DOCTYPE html>
<html lang="ru">
<nav class="navbar">
    <div class="navbar-container">
        <div class="logo">Gym Assistant</div>
        <button class="mobile-nav-toggle" aria-label="Toggle navigation">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </button>
        <ul class="nav-links">
            <li><a href="index.php">Главная</a></li>
        </ul>
    </div>
</nav>
<script>
    var nutritionData = <?php echo json_encode($nutrition_data ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
    var vitaminData = <?php echo json_encode($vitamin_data ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP); ?>;
</script>


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали клиента</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="icon.png?">
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

    <div class="subscription-block">
    <div class="subscription-info">
        <strong>Подписка активна до:</strong>
        <div class="subscription-status-box <?= $is_expired ? 'status-expired' : 'status-active' ?>">
            <?= $active_till ? date("d.m.Y H:i", strtotime($active_till)) : "Неизвестно" ?>
        </div>
        <?php if ($is_expired): ?>
            <p class="subscription-warning">⚠ Подписка истекла! Продлите её.</p>
        <?php endif; ?>
    </div>

    <!-- Форма продления подписки -->
    <form method="POST" class="subscription-actions">
        <label for="d" class="subscription-label">Выберите новую дату окончания подписки:</label>
        <div class="input-group required">
            <input id="d" type="text" name="subscription_date" class="subscription-date-picker" readonly onclick="calender(this)">
            <i class="fa fa-calendar-alt"></i>
        </div>

        <div class="subscription-buttons">
            <button type="submit" name="extend_subscription" class="btn btn-extend">🔄 Продлить</button>
            <button type="submit" name="cancel_subscription" class="btn btn-cancel">❌ Отменить подписку</button>
        </div>
    </form>
</div>


    <!-- Блок уведомлений -->
    <?php if (isset($_GET['food_deleted'])): ?>
        <div id="notification" class="notification">✅ Запись успешно удалена!</div>
        <script>
            setTimeout(function() {
                let notification = document.getElementById("notification");
                if (notification) {
                    notification.style.opacity = "0";
                    setTimeout(() => { notification.style.display = "none"; }, 500);
                }

                // Убираем параметр из URL после показа уведомления
                let url = new URL(window.location.href);
                url.searchParams.delete("food_deleted");
                window.history.replaceState({}, document.title, url);
            }, 5000);
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['subscription_cancelled'])): ?>
    <div id="notification" class="notification">⚠ Подписка отменена!</div>
    <script>
        setTimeout(function() {
            let notification = document.getElementById("notification");
            if (notification) {
                notification.style.opacity = "0";
                setTimeout(() => { notification.style.display = "none"; }, 500);
            }
            let url = new URL(window.location.href);
            url.searchParams.delete("subscription_cancelled");
            window.history.replaceState({}, document.title, url);
        }, 5000);
    </script>
<?php endif; ?>


    <?php if (isset($_GET['subscription_updated'])): ?>
        <div id="notification" class="notification">✅ Подписка успешно продлена!</div>
    <?php endif; ?>

    <br></br>
    <h1 class="page-banner">Персональная информация</h1>

    <?php if (isset($_GET['updated'])): ?>
            <div id="notification" class="notification">✅ Данные успешно обновлены!</div>
        <?php endif; ?>

<!-- Форма редактирования -->
<form method="POST">

    <div class="input-group required">
        <input type="text" name="full_name" id="full_name" value="<?php echo htmlspecialchars($full_name); ?>" required>
        <label for="full_name">Имя</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <div class="input-group required">
        <input type="number" name="age" id="age" value="<?php echo htmlspecialchars($age); ?>" required>
        <label for="age">Возраст</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <div class="input-group required">
        <input type="number" name="height" id="height" value="<?php echo htmlspecialchars($height); ?>" required>
        <label for="height">Рост (см)</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <div class="input-group required">
        <input type="number" name="weight" id="weight" value="<?php echo htmlspecialchars($weight); ?>" required>
        <label for="weight">Вес (кг)</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <div class="input-group required">
        <select name="gender" id="gender" required>
            <option value="male" <?php echo ($gender == 'male') ? 'selected' : ''; ?>>Мужской</option>
            <option value="female" <?php echo ($gender == 'female') ? 'selected' : ''; ?>>Женский</option>
        </select>
        <label for="gender">Пол</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <div class="input-group">
        <input type="text" name="additional_info" id="additional_info" value="<?php echo htmlspecialchars($additional_info); ?>">
        <label for="additional_info">Доп. информация</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <button type="submit" name="save_changes" class="btn save-btn">💾 Сохранить</button>
</form>




    <h1 class="page-banner">Данные по питанию</h1>
    <form>
<div class="dropdown-container">
    <!-- Первый выпадающий список -->
    <div class="dropdown">
        <input class="dropdown-toggle" type="checkbox" id="dropdown-group"/>
        <label class="dropdown-label" for="dropdown-group">
            <span id="selected-group">По приёмам пищи</span>
            <i class="uil uil-arrow-down"></i>
        </label>
        <div class="dropdown-menu">
            <a href="#" onclick="setDropdownValue('dropdown-group', 'meals', 'По приёмам пищи')">По приёмам пищи</a>
            <a href="#" onclick="setDropdownValue('dropdown-group', 'days', 'По дням')">По дням</a>
        </div>
    </div>

    <!-- Второй выпадающий список -->
    <div class="dropdown">
        <input class="dropdown-toggle" type="checkbox" id="dropdown-nutrient"/>
        <label class="dropdown-label" for="dropdown-nutrient">
            <span id="selected-nutrient">КБЖУ</span>
            <i class="uil uil-arrow-down"></i>
        </label>
        <div class="dropdown-menu">
            <a href="#" onclick="setDropdownValue('dropdown-nutrient', 'kbju', 'КБЖУ')">КБЖУ</a>
            <a href="#" onclick="setDropdownValue('dropdown-nutrient', 'vitamins', 'Витамины и минералы')">Витамины и минералы</a>
        </div>
    </div>
</div>

<div class="scrollbar" id="nutritionTableContainer" style="max-height: 270px; overflow-y: auto;">
    <table border="1" id="nutritionTable">
        <thead></thead>
        <tbody></tbody>
    </table>
</div>
    </form>


    <h1 class="page-banner">Рекомендации по питанию</h1>

<!-- Форма с рекомендациями -->
<form method="POST">
    <!-- Цель (выбор из списка) -->
    <div class="section-header">Цель</div>
    <div class="input-group required">
        <select name="aim" id="aim">
            <option value="Снижение веса" <?php echo ($recommendations['nutrition_recommendation']['aim'] ?? '') == "Снижение веса" ? "selected" : ""; ?>>Снижение веса</option>
            <option value="Поддержание веса" <?php echo ($recommendations['nutrition_recommendation']['aim'] ?? '') == "Поддержание веса" ? "selected" : ""; ?>>Поддержание веса</option>
            <option value="Набор веса" <?php echo ($recommendations['nutrition_recommendation']['aim'] ?? '') == "Набор веса" ? "selected" : ""; ?>>Набор веса</option>
        </select>
        <label for="aim">Цель</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <!-- Блок с метаболизмом -->
    <div class="section-header">Метаболизм</div>
    <div class="input-group">
        <input type="text" id="metabolism_basal" name="metabolism_basal" value="<?php echo htmlspecialchars($recommendations['nutrition_recommendation']['metabolism']['basal'] ?? 0); ?>">
        <label>Базальный метаболизм (ккал)</label>
        <i class="fa fa-chart-line"></i>
    </div>

    <div class="input-group">
        <input type="text" id="metabolism_maintenance" name="metabolism_maintenance" value="<?php echo htmlspecialchars($recommendations['nutrition_recommendation']['metabolism']['maintenance'] ?? 0); ?>">
        <label>Поддержание (ккал)</label>
        <i class="fa fa-balance-scale"></i>
    </div>

    <div class="input-group">
        <input type="text" id="metabolism_bulking" name="metabolism_bulking" value="<?php echo htmlspecialchars($recommendations['nutrition_recommendation']['metabolism']['bulking'] ?? 0); ?>">
        <label>Набор (ккал)</label>
        <i class="fa fa-arrow-up"></i>
    </div>

    <div class="input-group">
        <input type="text" id="metabolism_cutting" name="metabolism_cutting" value="<?php echo htmlspecialchars($recommendations['nutrition_recommendation']['metabolism']['cutting'] ?? 0); ?>">
        <label>Снижение (ккал)</label>
        <i class="fa fa-arrow-down"></i>
    </div>

<!-- БЖУ -->
<div class="section-header">БЖУ</div>
<div class="input-group">
    <input type="number" step="0.1" id="proteins" name="proteins" value="<?php echo number_format($recommendations['nutrition_recommendation']['nutrients_per_kg']['proteins'] ?? 0, 1, '.', ''); ?>">
    <label>Белки (г/кг)</label>
    <i class="fa fa-egg"></i>
</div>

<div class="input-group">
    <input type="number" step="0.1" id="fats" name="fats" value="<?php echo number_format($recommendations['nutrition_recommendation']['nutrients_per_kg']['fats'] ?? 0, 1, '.', ''); ?>">
    <label>Жиры (г/кг)</label>
    <i class="fa fa-tint"></i>
</div>

<div class="input-group">
    <input type="number" step="0.1" id="carbohydrates" name="carbohydrates" value="<?php echo number_format($recommendations['nutrition_recommendation']['nutrients_per_kg']['carbohydrates'] ?? 0, 1, '.', ''); ?>">
    <label>Углеводы (г/кг)</label>
    <i class="fa fa-bread-slice"></i>
</div>



    <!-- Поле "Другие рекомендации" -->
    <div class="section-header">Дополнительно</div>
    <label>Рекомендации по питанию</label>
    <div class="textarea-group">
    <textarea name="other_recommendations" id="other_recommendations" rows="4" class="no-resize" onfocus="toggleLabel(this)" oninput="toggleLabel(this)" onblur="toggleLabel(this)"><?php echo htmlspecialchars($recommendations['nutrition_recommendation']['other_recommendations'] ?? ""); ?></textarea>
    </div>
<!-- Новое текстовое поле -->
    <label>Рекомендации по тренировкам</label>
    <div class="textarea-group">
    <textarea name="training_recommendations" id="training_recommendations" rows="4" class="no-resize" onfocus="toggleLabel(this)" oninput="toggleLabel(this)" onblur="toggleLabel(this)"><?php echo htmlspecialchars($recommendations['training_recommendation'] ?? ""); ?></textarea>
    </div>
    

    <!-- Кнопка сохранить -->
    <button type="submit" name="save_nutrition_recommendations" class="btn save-btn">💾 Сохранить рекомендации</button>
</form>

        <!-- Блок для добавления тренировок -->
        <br><br>
        <br><br>
        <h1 class="page-banner">Журнал тренировок</h1>

<?php if (isset($_GET['training_added'])): ?>
    <div id="notification" class="notification">✅ Тренировка успешно добавлена!</div>
<?php endif; ?>

<!-- Форма добавления новой тренировки -->
<form method="POST" id="trainingForm">
    <div class="input-group required">
        <div id="muscle_select" class="picklist">
            <div class="picklist-option" data-value="спина">Спина</div>
            <div class="picklist-option" data-value="грудь">Грудь</div>
            <div class="picklist-option" data-value="дельты">Дельты</div>
            <div class="picklist-option" data-value="бицепс">Бицепс</div>
            <div class="picklist-option" data-value="трицепс">Трицепс</div>
            <div class="picklist-option" data-value="ноги">Ноги</div>
            <div class="picklist-option" data-value="ягодицы">Ягодицы</div>
        </div>
    </div>

    <!-- Контейнер для выбранных групп -->
    <div id="selected_muscles" class="selected-muscles"></div>

    <!-- Скрытое поле для передачи данных в PHP -->
    <input type="hidden" name="muscle_group" id="muscle_group_input">

    <button type="submit" name="add_training" class="btn save-btn">➕ Добавить тренировку</button>
</form>

<br><br>
<!-- Таблица с добавленными тренировками -->
<div class="scrollbar" id="trainingTableContainer" style="max-height: 300px; overflow-y: auto;">
    <table border="1" id="trainingTable">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Группы мышц</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Получаем JSON с тренировками
            $sql_fetch = "SELECT trainings FROM user_info WHERE tg_id = $client_id";
            $result_fetch = $conn->query($sql_fetch);
            $row_fetch = $result_fetch->fetch_assoc();
            $trainings = !empty($row_fetch['trainings']) ? json_decode($row_fetch['trainings'], true) : [];

            if (!empty($trainings)) {
                // Сортируем тренировки по убыванию даты (новые сверху)
                usort($trainings, function ($a, $b) {
                    return strtotime($b['date']) - strtotime($a['date']);
                });

                // Выводим отсортированные тренировки
                foreach ($trainings as $training) {
                    $date = date("d.m.Y", strtotime($training['date']));
                    $groups = implode(", ", $training['muscle_group']);
                    echo "<tr><td>$date</td><td>$groups</td></tr>";
                }
            } else {
                echo "<tr><td colspan='2'>Записей нет</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

        <!-- Back Button -->
        <a href="index.php" class="button back-button">⬅ Вернуться к списку</a>
    </div>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const picklistOptions = document.querySelectorAll(".picklist-option");
    const selectedMusclesContainer = document.getElementById("selected_muscles");
    const muscleGroupsInput = document.getElementById("muscle_group_input");
    let selectedMuscles = [];

    // Обработка кликов по пиклисту
    picklistOptions.forEach(option => {
        option.addEventListener("click", function () {
            const value = this.getAttribute("data-value");

            if (selectedMuscles.includes(value)) {
                // Убираем из списка
                selectedMuscles = selectedMuscles.filter(muscle => muscle !== value);
                this.classList.remove("selected");
                updateSelectedMuscles();
            } else {
                // Добавляем в список
                selectedMuscles.push(value);
                this.classList.add("selected");
                updateSelectedMuscles();
            }
        });
    });

    // Функция обновления отображаемых мышц
    function updateSelectedMuscles() {
        selectedMusclesContainer.innerHTML = "";
        selectedMuscles.forEach(muscle => {
            const div = document.createElement("div");
            div.classList.add("selected-muscle");
            div.innerHTML = `${muscle} <span class="remove-btn" data-value="${muscle}">❌</span>`;
            selectedMusclesContainer.appendChild(div);
        });

        // Обновляем скрытое поле для передачи данных в PHP
        muscleGroupsInput.value = JSON.stringify(selectedMuscles);

        // Добавляем обработчик удаления
        document.querySelectorAll(".remove-btn").forEach(button => {
            button.addEventListener("click", function () {
                const value = this.getAttribute("data-value");
                selectedMuscles = selectedMuscles.filter(muscle => muscle !== value);
                document.querySelector(`[data-value="${value}"]`).classList.remove("selected");
                updateSelectedMuscles();
            });
        });
    }
});

document.addEventListener("DOMContentLoaded", function () {
    let notification = document.getElementById('notification');

    if (notification) {
        console.log("✅ Уведомление найдено, запускаем анимацию!");

        // Показываем уведомление
        notification.style.visibility = "visible";
        notification.style.opacity = "1";

        // Убираем только ?updated=true, оставляя tg_id
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has("updated")) {
            urlParams.delete("updated"); // ❌ Удаляем только updated, а не весь URL

            const newUrl = window.location.pathname + "?" + urlParams.toString();
            window.history.replaceState({}, document.title, newUrl);
            console.log("🔄 Обновленный URL:", newUrl);
        }

        // Через 6 секунд скрываем уведомление
        setTimeout(function() {
            notification.style.opacity = "0";
            setTimeout(() => {
                notification.style.visibility = "hidden";
            }, 500);
        }, 6000);
    }
});

let selectedViewType = "meals"; // По умолчанию "по приёмам пищи"
    let selectedNutrientType = "kbju"; // По умолчанию "КБЖУ"

    function setDropdownValue(dropdownId, value, text) {
    document.getElementById(dropdownId).checked = false; // Закрываем меню
    
    // Сохраняем текущий scroll position перед обновлением
    let scrollPosition = window.scrollY || document.documentElement.scrollTop;

    if (dropdownId === "dropdown-group") {
        selectedViewType = value;
        document.getElementById("selected-group").innerText = text;
    } else if (dropdownId === "dropdown-nutrient") {
        selectedNutrientType = value;
        document.getElementById("selected-nutrient").innerText = text;
    }

    // Обновляем таблицу, но не двигаем пользователя с его места
    updateTable();

    // Восстанавливаем положение страницы после обновления данных
    setTimeout(() => {
        window.scrollTo(0, scrollPosition);
    }, 10); // Ждём немного, пока браузер обработает изменения
}


    function updateTable() {
        let table = document.getElementById("nutritionTable");
        let thead = table.querySelector("thead");
        let tbody = table.querySelector("tbody");
        tbody.innerHTML = ""; // Очищаем таблицу перед обновлением

        if (selectedNutrientType === "kbju") {
            if (selectedViewType === "meals") {
                renderMealsTable();
            } else {
                renderDaysTable();
            }
        } else {
            if (selectedViewType === "meals") {
                renderVitaminsTable();
            } else {
                renderVitaminsDaysTable();
            }
        }
    }

    function renderMealsTable() {
    let tbody = document.getElementById("nutritionTable").querySelector("tbody");
    let thead = document.getElementById("nutritionTable").querySelector("thead");

    // Обновляем заголовок таблицы
    thead.innerHTML = `
    <tr>
        <th>Дата</th>
        <th>Блюдо</th>
        <th>Протеины</th>
        <th>Жиры</th>
        <th>Углеводы</th>
        <th>Калорийность</th>
        <th>Действие</th>
    </tr>`;

    tbody.innerHTML = ""; // Очищаем таблицу перед обновлением

    // Передаем данные из PHP в JavaScript
    let nutritionData = <?php echo json_encode($nutrition_data, JSON_UNESCAPED_UNICODE); ?>;

    if (!nutritionData || typeof nutritionData !== "object") {
        console.error("Ошибка: nutritionData не является объектом", nutritionData);
        return;
    }

    // Перебираем даты (отсортированные по убыванию)
    let sortedDates = Object.keys(nutritionData).sort((a, b) => new Date(b) - new Date(a));

    sortedDates.forEach(date => {
        let foods = Object.values(nutritionData[date]); // Преобразуем объект в массив


        // Проверяем, является ли `foods` массивом
        if (!Array.isArray(foods)) {
            console.warn(`Предупреждение: Данные за ${date} не являются массивом`, foods);
            return;
        }

        foods.forEach(food => {
            let row = document.createElement("tr");

            row.innerHTML = `
                <td>${date}</td>
                <td>${food.food_item ?? 'Неизвестно'}</td>
                <td>${food.proteins ?? 0} г</td>
                <td>${food.fats ?? 0} г</td>
                <td>${food.carbohydrates ?? 0} г</td>
                <td>${food.calories ?? 0} ккал</td>
                <td>
                    <form method="POST" style="display: contents; margin: 0; padding: 0;">
                        <input type="hidden" name="food_date" value="${date}">
                        <input type="hidden" name="food_name" value="${food.food_item}">
                        <button type="submit" name="delete_food" class="btn delete-btn"><span class="button-content">Удалить</span></button>
                    </form>
                </td>
            `;

            tbody.appendChild(row);
        });
    });
}



    function renderDaysTable() {
        let tbody = document.getElementById("nutritionTable").querySelector("tbody");
        let thead = document.getElementById("nutritionTable").querySelector("thead");
        thead.innerHTML = `
            <tr>
                <th>Дата</th>
                <th>Общие протеины</th>
                <th>Общие жиры</th>
                <th>Общие углеводы</th>
                <th>Общая калорийность</th>
            </tr>`;

        <?php
        krsort($nutrition_data);
        foreach ($nutrition_data as $date => $foods) {
            $total_proteins = $total_fats = $total_carbs = $total_calories = 0;
            foreach ($foods as $food) {
                $total_proteins += $food['proteins'] ?? 0;
                $total_fats += $food['fats'] ?? 0;
                $total_carbs += $food['carbohydrates'] ?? 0;
                $total_calories += $food['calories'] ?? 0;
            }
            echo "tbody.innerHTML += `<tr>
                <td>" . htmlspecialchars($date) . "</td>
                <td>" . htmlspecialchars($total_proteins) . " г</td>
                <td>" . htmlspecialchars($total_fats) . " г</td>
                <td>" . htmlspecialchars($total_carbs) . " г</td>
                <td>" . htmlspecialchars($total_calories) . " ккал</td>
            </tr>`;";
        }
        ?>
    }

    function renderVitaminsTable() {
        let tbody = document.getElementById("nutritionTable").querySelector("tbody");
        let thead = document.getElementById("nutritionTable").querySelector("thead");
        thead.innerHTML = `
            <tr>
                <th>Дата</th>
                <th>Продукт</th>
                <th>A</th>
                <th>C</th>
                <th>D</th>
                <th>E</th>
                <th>K</th>
                <th>B1</th>
                <th>B2</th>
                <th>B6</th>
                <th>B9</th>
                <th>B12</th>
                <th>Железо</th>
                <th>Цинк</th>
                <th>Медь</th>
                <th>Кальций</th>
                <th>Магний</th>
            </tr>`;

        <?php
        krsort($nutrition_data);
        foreach ($nutrition_data as $date => $foods) {
            foreach ($foods as $food) {
                if (isset($food['A']) || isset($food['C']) || isset($food['iron'])) {
                    echo "tbody.innerHTML += `<tr>
                        <td>" . htmlspecialchars($date) . "</td>
                        <td>" . htmlspecialchars($food['food_item'] ?? 'Неизвестно') . "</td>
                        <td>" . htmlspecialchars($food['A'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['C'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['D'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['E'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['K'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['B1'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['B2'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['B6'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['B9'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['B12'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['iron'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['zinc'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['copper'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['calcium'] ?? 0) . "</td>
                        <td>" . htmlspecialchars($food['magnesium'] ?? 0) . "</td>
                    </tr>`;";
                }
            }
        }
        ?>
    }

    function renderVitaminsDaysTable() {
        let tbody = document.getElementById("nutritionTable").querySelector("tbody");
        let thead = document.getElementById("nutritionTable").querySelector("thead");
        thead.innerHTML = `
            <tr>
                <th>Дата</th>
                <th>A</th>
                <th>C</th>
                <th>D</th>
                <th>E</th>
                <th>K</th>
                <th>B1</th>
                <th>B2</th>
                <th>B6</th>
                <th>B9</th>
                <th>B12</th>
                <th>Железо</th>
                <th>Цинк</th>
                <th>Медь</th>
                <th>Кальций</th>
                <th>Магний</th>
            </tr>`;

        <?php
        krsort($nutrition_data);
        foreach ($nutrition_data as $date => $foods) {
            // Суммируем витамины и минералы за день
            $totals = array_fill_keys(['A', 'C', 'D', 'E', 'K', 'B1', 'B2', 'B6', 'B9', 'B12', 'iron', 'zinc', 'copper', 'calcium', 'magnesium'], 0);
            foreach ($foods as $food) {
                foreach ($totals as $key => $value) {
                    $totals[$key] += $food[$key] ?? 0;
                }
            }
            echo "tbody.innerHTML += `<tr><td>" . htmlspecialchars($date) . "</td>";
            foreach ($totals as $value) echo "<td>" . htmlspecialchars($value) . "</td>";
            echo "</tr>`;";
        }
        ?>
    }
    function toggleLabel(textarea) {
    let label = textarea.previousElementSibling;
    if (textarea.value.trim() !== "" || document.activeElement === textarea) {
        label.style.display = "none"; // Прячем label
    } else {
        label.style.display = "block"; // Показываем label, если пусто
    }
    }

    function calender(e) {
    if (document.getElementById("calenderMain")) {
        document.getElementById("calenderMain").remove();
        return;
    }

    let date = new Date();
    let currMonth = date.getMonth();
    let currYear = date.getFullYear();

    let monthArray = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];

    let cal = `<div id="calenderMain">
        <div class="overlay"></div> <!-- Фон, чтобы ловить клики вне календаря -->
        <div class="main">
            <div class="close-btn" onclick="closeCalender()">✖</div> <!-- Кнопка закрытия -->
            <div class="yearDiv">
                <span class="left" onclick="changeYear(-1)">❮</span>
                <span id="year">${currYear}</span>
                <span class="right" onclick="changeYear(1)">❯</span>
            </div>
            <div class="monthDiv">
                <span class="left" onclick="changeMonth(-1)">❮</span>
                <span id="month">${monthArray[currMonth]}</span>
                <span class="right" onclick="changeMonth(1)">❯</span>
            </div>
            <table id="fillDate">
                <tr class="weekT">
                    <td class="wDay">Пн</td><td class="wDay">Вт</td><td class="wDay">Ср</td>
                    <td class="wDay">Чт</td><td class="wDay">Пт</td><td class="wDay">Сб</td><td class="wDay">Вс</td>
                </tr>
            </table>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', cal);
    document.querySelector(".main").style.display = "block";

    window.currentMonth = currMonth;
    window.currentYear = currYear;
    window.targetInput = e;

    // Добавляем событие для закрытия при клике вне календаря
    document.querySelector(".overlay").addEventListener("click", closeCalender);

    setCalender(currMonth, currYear);
}

function setCalender(month, year) {
    let days = [];
    let date = new Date(year, month, 1);
    let monthArray = ["Январь", "Февраль", "Март", "Апрель", "Май", "Июнь", "Июль", "Август", "Сентябрь", "Октябрь", "Ноябрь", "Декабрь"];

    document.getElementById("month").innerText = monthArray[month];
    document.getElementById("year").innerText = year;

    let fillDate = document.getElementById("fillDate");
    fillDate.querySelectorAll("tr:not(.weekT)").forEach(row => row.remove());

    while (date.getMonth() === month) {
        days.push(new Date(date));
        date.setDate(date.getDate() + 1);
    }

    let row = document.createElement("tr");
    fillDate.appendChild(row);

    for (let i = 0; i < days[0].getDay() - 1; i++) {
        row.appendChild(document.createElement("td"));
    }

    days.forEach(day => {
        if (day.getDay() === 1 && row.children.length > 0) {
            row = document.createElement("tr");
            fillDate.appendChild(row);
        }
        let cell = document.createElement("td");
        cell.innerText = day.getDate();
        cell.classList.add("date");
        cell.onclick = function() {
            let selectedDate = `${year}-${String(month + 1).padStart(2, "0")}-${String(day.getDate()).padStart(2, "0")}`;
            window.targetInput.value = selectedDate;
            closeCalender(); // Закрываем календарь после выбора
        };
        row.appendChild(cell);
    });
}

function changeMonth(step) {
    window.currentMonth += step;
    if (window.currentMonth < 0) {
        window.currentMonth = 11;
        window.currentYear--;
    } else if (window.currentMonth > 11) {
        window.currentMonth = 0;
        window.currentYear++;
    }
    setCalender(window.currentMonth, window.currentYear);
}

function changeYear(step) {
    window.currentYear += step;
    setCalender(window.currentMonth, window.currentYear);
}

function closeCalender() {
    let calenderEl = document.getElementById("calenderMain");
    if (calenderEl) {
        calenderEl.remove();
    }
}

    // Загружаем таблицу по умолчанию
    window.onload = function() {
        updateTable();
    };    

</script>
</body>
</html>

<?php
$conn->close();
?>