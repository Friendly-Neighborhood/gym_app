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

// Handle recommendation save
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_recommendations'])) {
    $nutrition_recommendation = $conn->real_escape_string($_POST['nutrition_recommendation']);
    $training_recommendation = $conn->real_escape_string($_POST['training_recommendation']);

    $recommendations_json = json_encode([
        "nutrition_recommendation" => $nutrition_recommendation,
        "training_recommendation" => $training_recommendation
    ], JSON_UNESCAPED_UNICODE);

    // ✅ Добавляем SQL-запрос
    $sql_update_recommendations = "UPDATE user_info SET recommendations = '$recommendations_json' WHERE tg_id = $client_id";

    if ($conn->query($sql_update_recommendations) === TRUE) {
        header("Location: details.php?tg_id=$client_id&updated=true");
        exit();
    } else {
        echo "<p>Ошибка обновления рекомендаций: " . $conn->error . "</p>";
    }
}

// Форма обработки добавления новой тренировки
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_training'])) {
    $muscle_group = $conn->real_escape_string($_POST['muscle_group']);
    $current_datetime = date("Y-m-d H:i:s");

    // Получаем текущие данные о тренировках
    $sql_fetch_trainings = "SELECT trainings FROM user_info WHERE tg_id = $client_id";
    $result_fetch_trainings = $conn->query($sql_fetch_trainings);
    $row_fetch_trainings = $result_fetch_trainings->fetch_assoc();
    
    // Декодируем JSON или создаем пустой массив, если данных нет
    $trainings = !empty($row_fetch_trainings['trainings']) ? json_decode($row_fetch_trainings['trainings'], true) : [];

    // Добавляем новую запись
    $trainings[] = [
        "date" => $current_datetime,
        "muscle_group" => $muscle_group
    ];

    // Сохраняем обновлённые данные в JSON
    $updated_trainings_json = json_encode($trainings, JSON_UNESCAPED_UNICODE);
    $sql_update_trainings = "UPDATE user_info SET trainings = '$updated_trainings_json' WHERE tg_id = $client_id";

    if ($conn->query($sql_update_trainings) === TRUE) {
        header("Location: details.php?tg_id=$client_id&training_added=true");
        exit();
    } else {
        echo "<p>Ошибка обновления тренировок: " . $conn->error . "</p>";
    }
}

// Получаем текущие тренировки
$sql_fetch_trainings = "SELECT trainings FROM user_info WHERE tg_id = $client_id";
$result_fetch_trainings = $conn->query($sql_fetch_trainings);
$row_fetch_trainings = $result_fetch_trainings->fetch_assoc();
$trainings = !empty($row_fetch_trainings['trainings']) ? json_decode($row_fetch_trainings['trainings'], true) : [];


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['save_nutrition_aim'])) {
    $aim = $conn->real_escape_string($_POST['aim']);

    // Обновляем JSON только с новой целью
    $sql_update_aim = "UPDATE user_info SET recommendations = JSON_SET(recommendations, '$.nutrition_recommendation.aim', '$aim') WHERE tg_id = $client_id";

    if ($conn->query($sql_update_aim) === TRUE) {
        header("Location: details.php?tg_id=$client_id&aim_updated=true");
        exit();
    } else {
        echo "<p>Ошибка обновления: " . $conn->error . "</p>";
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
        <select name="aim" id="aim" required onchange="updateValuesBasedOnAim()">
            <option value="Снижение веса" <?php echo ($nutrition_data['aim'] ?? '') == "Снижение веса" ? "selected" : ""; ?>>Снижение</option>
            <option value="Поддержание" <?php echo ($nutrition_data['aim'] ?? '') == "Поддержание" ? "selected" : ""; ?>>Поддержание</option>
            <option value="Набор" <?php echo ($nutrition_data['aim'] ?? '') == "Набор" ? "selected" : ""; ?>>Набор</option>
        </select>
        <label for="aim">Цель</label>
        <i class="fa fa-check-circle"></i>
    </div>

    <!-- Блок с метаболизмом -->
    <div class="section-header">Метаболизм</div>
    <div class="input-group">
        <input type="text" id="metabolism_basal" value="<?php echo htmlspecialchars($nutrition_data['metabolism']['basal'] ?? 0); ?>" readonly>
        <label>Базальный метаболизм (ккал)</label>
        <i class="fa fa-chart-line"></i>
    </div>

    <div class="input-group">
        <input type="text" id="metabolism_maintenance" value="<?php echo htmlspecialchars($nutrition_data['metabolism']['maintenance'] ?? 0); ?>" readonly>
        <label>Поддержание (ккал)</label>
        <i class="fa fa-balance-scale"></i>
    </div>

    <div class="input-group">
        <input type="text" id="metabolism_bulking" value="<?php echo htmlspecialchars($nutrition_data['metabolism']['bulking'] ?? 0); ?>" readonly>
        <label>Набор массы (ккал)</label>
        <i class="fa fa-arrow-up"></i>
    </div>

    <div class="input-group">
        <input type="text" id="metabolism_cutting" value="<?php echo htmlspecialchars($nutrition_data['metabolism']['cutting'] ?? 0); ?>" readonly>
        <label>Снижение веса (ккал)</label>
        <i class="fa fa-arrow-down"></i>
    </div>

    <!-- БЖУ -->
    <div class="section-header">БЖУ</div>
    <div class="input-group">
        <input type="text" id="proteins" value="<?php echo htmlspecialchars($nutrition_data['nutrients_per_kg']['proteins'] ?? 0); ?>" readonly>
        <label>Белки (г/кг)</label>
        <i class="fa fa-egg"></i>
    </div>

    <div class="input-group">
        <input type="text" id="fats" value="<?php echo htmlspecialchars($nutrition_data['nutrients_per_kg']['fats'] ?? 0); ?>" readonly>
        <label>Жиры (г/кг)</label>
        <i class="fa fa-tint"></i>
    </div>

    <div class="input-group">
        <input type="text" id="carbohydrates" value="<?php echo htmlspecialchars($nutrition_data['nutrients_per_kg']['carbohydrates'] ?? 0); ?>" readonly>
        <label>Углеводы (г/кг)</label>
        <i class="fa fa-bread-slice"></i>
    </div>

    <!-- Поле "Другие рекомендации" теперь можно редактировать -->
    <div class="section-header">Дополнительно</div>
    <div class="textarea-group">
    <label for="other_recommendations" class="floating-label">Другие рекомендации</label>
    <textarea name="other_recommendations" id="other_recommendations" rows="4" class="no-resize" onfocus="toggleLabel(this)" oninput="toggleLabel(this)" onblur="toggleLabel(this)"><?php echo htmlspecialchars($nutrition_data['other_recommendations'] ?? ""); ?></textarea>
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
<form method="POST">
    <div class="input-group required">
        <select name="muscle_group" required>
            <option value="спина">Спина</option>
            <option value="грудь">Грудь</option>
            <option value="дельты">Дельты</option>
            <option value="бицепс">Бицепс</option>
            <option value="трицепс">Трицепс</option>
            <option value="ноги">Ноги</option>
            <option value="ягодицы">Ягодицы</option>
        </select>
        <label for="muscle_group">Группа мышц</label>
        <i class="fa fa-check-circle"></i>
    </div>
    <button type="submit" name="add_training" class="btn save-btn">➕ Добавить тренировку</button>
</form>

<br><br>
<!-- Таблица с добавленными тренировками -->
<div class="scrollbar" id="trainingTableContainer" style="max-height: 300px; overflow-y: auto;">
    <table border="1" id="trainingTable">
        <thead>
            <tr>
                <th>Дата</th>
                <th>Группа мышц</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if (!empty($trainings)) {
                foreach (array_reverse($trainings) as $training) { // Выводим последние записи первыми
                    echo "<tr>
                            <td>" . date("d.m.Y H:i", strtotime($training['date'])) . "</td>
                            <td>" . htmlspecialchars($training['muscle_group']) . "</td>
                          </tr>";
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
        thead.innerHTML = `
            <tr>
                <th>Дата</th>
                <th>Блюдо</th>
                <th>Протеины</th>
                <th>Жиры</th>
                <th>Углеводы</th>
                <th>Калорийность</th>
            </tr>`;

        <?php
        krsort($nutrition_data);
        foreach ($nutrition_data as $date => $foods) {
            foreach ($foods as $food) {
                echo "tbody.innerHTML += `<tr>
                    <td>" . htmlspecialchars($date) . "</td>
                    <td>" . htmlspecialchars($food['food_item'] ?? 'Нет данных') . "</td>
                    <td>" . htmlspecialchars($food['proteins'] ?? 0) . " г</td>
                    <td>" . htmlspecialchars($food['fats'] ?? 0) . " г</td>
                    <td>" . htmlspecialchars($food['carbohydrates'] ?? 0) . " г</td>
                    <td>" . htmlspecialchars($food['calories'] ?? 0) . " ккал</td>
                </tr>`;";
            }
        }
        ?>
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

    // Загружаем таблицу по умолчанию
    window.onload = function() {
        updateTable();
    };

    if (urlParams.has("training_added")) {
    urlParams.delete("training_added");

    const newUrl = window.location.pathname + "?" + urlParams.toString();
    window.history.replaceState({}, document.title, newUrl);
}    

function updateValuesBasedOnAim() {
        var selectedAim = document.getElementById('aim').value;
        var basal = <?php echo json_encode($nutrition_data['metabolism']['basal'] ?? 0); ?>;
        var maintenance = <?php echo json_encode($nutrition_data['metabolism']['maintenance'] ?? 0); ?>;
        var bulking = <?php echo json_encode($nutrition_data['metabolism']['bulking'] ?? 0); ?>;
        var cutting = <?php echo json_encode($nutrition_data['metabolism']['cutting'] ?? 0); ?>;

        var proteins = <?php echo json_encode($nutrition_data['nutrients_per_kg']['proteins'] ?? 0); ?>;
        var fats = <?php echo json_encode($nutrition_data['nutrients_per_kg']['fats'] ?? 0); ?>;
        var carbohydrates = <?php echo json_encode($nutrition_data['nutrients_per_kg']['carbohydrates'] ?? 0); ?>;

        document.getElementById('metabolism_basal').value = basal;
        document.getElementById('metabolism_maintenance').value = maintenance;
        document.getElementById('metabolism_bulking').value = bulking;
        document.getElementById('metabolism_cutting').value = cutting;

        document.getElementById('proteins').value = proteins;
        document.getElementById('fats').value = fats;
        document.getElementById('carbohydrates').value = carbohydrates;
    }

    function toggleLabel(textarea) {
    let label = textarea.previousElementSibling;
    if (textarea.value.trim() !== "" || document.activeElement === textarea) {
        label.style.display = "none"; // Прячем label
    } else {
        label.style.display = "block"; // Показываем label, если пусто
    }
    }
</script>
</body>
</html>

<?php
$conn->close();
?>