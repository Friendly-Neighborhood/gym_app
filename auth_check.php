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

// Функция запроса аутентификации
function requestAuth() {
    header('WWW-Authenticate: Basic realm="Введите логин и пароль"');
    header('HTTP/1.0 401 Unauthorized');
    echo "🚫 Доступ запрещён. Пожалуйста, введите корректные данные.";
    exit();
}

// Очищаем кэш браузера и сбрасываем аутентификацию
header("Cache-Control: no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

// Принудительно разлогиниваем при обновлении страницы
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) {
    requestAuth();
}

// Проверяем логин и пароль
$login = $conn->real_escape_string($_SERVER['PHP_AUTH_USER']);
$password = $conn->real_escape_string($_SERVER['PHP_AUTH_PW']);

$sql = "SELECT * FROM login WHERE login = '$login' AND password = '$password'";
$result = $conn->query($sql);

// Если неверный логин, снова запрашиваем аутентификацию
if ($result->num_rows === 0) {
    requestAuth();
}

// **Важно**: Принудительно сбрасываем логин после загрузки страницы
unset($_SERVER['PHP_AUTH_USER']);
unset($_SERVER['PHP_AUTH_PW']);
?>