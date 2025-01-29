<?php
$servername = "drbgz515.mysql.network:10501";
$username = "gym_admin";
$password = "jALub29P75";
$dbname = "gym_asisstant_clients";

// Подключение к БД
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Функция для проверки логина и пароля
function authenticate($conn) {
    if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
        requestAuth();
    }

    $login = $conn->real_escape_string($_SERVER['PHP_AUTH_USER']);
    $password = $conn->real_escape_string($_SERVER['PHP_AUTH_PW']);

    // Проверка в базе данных
    $sql = "SELECT * FROM login WHERE login = '$login' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result->num_rows === 0) {
        requestAuth(); // Если неверные данные → снова запросить логин
    }
}

// Функция запроса логина и пароля
function requestAuth() {
    header('WWW-Authenticate: Basic realm="Доступ закрыт"');
    header('HTTP/1.0 401 Unauthorized');
    echo "🚫 Доступ запрещён. Пожалуйста, введите корректные данные.";
    exit();
}

// Проверяем логин
authenticate($conn);
?>