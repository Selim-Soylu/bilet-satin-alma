<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

require_once '../src/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = 'Tüm alanlar zorunludur.';
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT * FROM User WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            if ($user['role'] === 'company') {
                $_SESSION['company_id'] = $user['company_id'];
            }
            
            header('Location: index.php');
            exit(); 
        } else {
            $error = 'Geçersiz e-posta veya şifre.';
        }
    }
}

include '../src/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h2>Giriş Yap</h2></div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div><?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                </form>
            </div>
             <div class="card-footer text-center">
                Hesabınız yok mu? <a href="register.php">Kayıt Olun</a>
            </div>
        </div>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>