<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn     = get_db_connection();
    $user_id  = (int)$_SESSION['user_id'];
    $build_id = (int)($_POST['build_id'] ?? 0);
    $new_name = trim($_POST['new_name'] ?? '');

    if (empty($new_name)) $new_name = "My Build";
    if (strlen($new_name) > 100) $new_name = substr($new_name, 0, 100);

    $stmt = $conn->prepare("UPDATE builds SET build_name=? WHERE build_id=? AND user_id=?");
    $stmt->bind_param("sii", $new_name, $build_id, $user_id);
    $stmt->execute();
    $stmt->close();
    $conn->close();
}

header("Location: my_builds.php?msg=updated");
exit();
?>
