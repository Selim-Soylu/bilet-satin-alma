<?php
function requireAuth($allowedRoles = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?error=Bu sayfayı görüntülemek için giriş yapmalısınız.');
        exit();
    }

    if (!empty($allowedRoles) && !in_array($_SESSION['role'], $allowedRoles)) {
        http_response_code(403); 
        include '../src/templates/header.php';
        echo "<div class='alert alert-danger'>Bu sayfayı görüntüleme yetkiniz yok.</div>";
        include '../src/templates/footer.php';
        exit();
    }
}
?>