<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Soylu Seyahat</title>
    <link rel="icon" type="image/png" href="assets/images/icon.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.css" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/awesomplete/1.1.5/awesomplete.min.js" async defer></script>
    <link rel="stylesheet" href="assets/css/style.css"> 
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="index.php">Soylu Seyahat</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <span class="navbar-text me-3">
                            Hoş geldiniz, <?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>
                        </span>
                    </li>
                    <?php if ($_SESSION['role'] === 'user'): ?>
                        <li class="nav-item"><a class="nav-link" href="hesabim.php">Hesabım</a></li>
                    <?php elseif ($_SESSION['role'] === 'admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="admin_panel.php">Admin Paneli</a></li>
                    <?php elseif ($_SESSION['role'] === 'company'): ?>
                         <li class="nav-item"><a class="nav-link" href="company_panel.php">Firma Paneli</a></li>
                    <?php endif; ?>
                    <li class="nav-item"><a class="nav-link" href="sifre_degistir.php">Şifre Değiştir</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
                    <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container mt-4">