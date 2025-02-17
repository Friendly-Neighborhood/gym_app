<?php
include 'auth_check.php'; // Запрос логина и пароля

$servername = "drbgz515.mysql.network:10501";
$username = "gym_admin";
$password = "jALub29P75";
$dbname = "gym_asisstant_clients";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch clients
$sql = "SELECT tg_id, personal_info, active_till FROM user_info_test";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<nav class="navbar_index">
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

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список клиентов</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="icon.png">
</head>
<body>
<h1 class="page-bannerindex">Список клиентов</h1>
    <table class="index_table" border="1">
        <tr>
            <th>ID телеграмма</th>
            <th>Имя клиента</th>
            <th>Активен до</th>
            <th>Детали</th>
        </tr>
        <?php
        $current_time = date("Y-m-d H:i:s"); // Текущее время

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $active_till = $row["active_till"] ?? "Неизвестно";
                $is_active = ($active_till > $current_time); // Проверяем активен ли юзер

                echo "<tr" . ($is_active ? "" : " style='background-color:rgb(248, 83, 83);'") . ">"; // Красный фон если неактивен
                echo "<td>" . $row["tg_id"] . "</td>";

                // Имя пользователя
                $personal_info = json_decode($row["personal_info"], true);
                $full_name = $personal_info["full_name"] ?? "Имя не добавлено";
                echo "<td>" . htmlspecialchars($full_name) . "</td>";

                // Активность
                echo "<td>" . htmlspecialchars($active_till) . "</td>";

                // Ссылка на детали с возможностью продления
                echo "<td><a href='details.php?tg_id=" . $row["tg_id"] . "' class='button details-button'>🔍 Детали</a></td>";
                echo "</tr>";          
            }
        } else {
            echo "<tr><td colspan='4'>Нет клиентов</td></tr>";
        }
        ?>
    </table>
</body>
</html>
<?php
$conn->close();
?>