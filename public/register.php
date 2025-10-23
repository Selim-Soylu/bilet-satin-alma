<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']); 
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($fullName) || empty($email) || empty($password)) {
        $error = 'Tüm alanlar zorunludur.';
    } else {
        $db = getDBConnection();
        $stmt = $db->prepare("SELECT id FROM User WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Bu e-posta adresi zaten kayıtlı.';
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userId = generate_uuid();
            $sql = "INSERT INTO User (id, full_name, email, password, role, balance) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $db->prepare($sql);
            
            if ($stmt->execute([$userId, $fullName, $email, $hashedPassword, 'user', 800.0])) {
                $success = 'Kayıt başarılı! Giriş sayfasına yönlendiriliyorsunuz...';
                header("Refresh:2; url=login.php");
            } else {
                $error = 'Kayıt sırasında bir hata oluştu.';
            }
        }
    }
}

include '../src/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><h2>Kayıt Ol</h2></div>
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
                <form action="register.php" method="POST">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Ad Soyad</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">E-posta Adresi</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Şifre</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
                </form>
            </div>
            <div class="card-footer text-center">
                Zaten bir hesabınız var mı? <a href="login.php">Giriş Yapın</a>
            </div>
        </div>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>