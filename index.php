<?php
ob_start();
session_start();
require_once('config/database.php');

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'admin') {
        header('Location: admin/dashboard.php');
        exit();
    } else if ($_SESSION['role'] === 'user') {
        header('Location: user/dashboard.php');
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image" href="uploads/background.jpg">
    <title>Pet Care</title>
</head>
<body>
    <?php
    header('Location: views/login.php');
    exit();
    ?>
</body>
</html>

<?php
ob_end_flush();
?>