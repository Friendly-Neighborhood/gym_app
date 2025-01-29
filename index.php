<?php
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
$sql = "SELECT tg_id, personal_info FROM user_info";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Список клиентов</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="icon.png">
</head>
<body>
<h3 class="animated-text4"><span></span></h3>
    <table border="1">
        <tr>
            <th>ID телеграмма</th>
            <th>Имя клиента</th>
            <th>Детали</th>
        </tr>
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $row["tg_id"] . "</td>";  // Display Telegram ID
                
                // Decode JSON and extract full_name
                $personal_info = json_decode($row["personal_info"], true);
                $full_name = isset($personal_info["full_name"]) ? $personal_info["full_name"] : "Имя не добавлено";
                
                echo "<td>" . htmlspecialchars($full_name) . "</td>";  // Display Full Name
                echo "<td><a href='details.php?tg_id=" . $row["tg_id"] . "' class='button details-button'>🔍 Детали</a></td>";
                echo "</tr>";          
            }
        } else {
            echo "<tr><td colspan='3'>No clients found</td></tr>";
        }
        ?>
    </table>
</body>
</html>

<?php
$conn->close();
?>