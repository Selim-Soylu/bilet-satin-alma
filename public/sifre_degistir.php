<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['user', 'company', 'admin']);

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = "Tüm alanlar zorunludur.";
    } else {
        $db = getDBConnection();
        
        $stmt = $db->prepare("SELECT password FROM User WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($current_password, $user['password'])) {
            
            if ($new_password !== $confirm_password) {
                $error = "Yeni şifreler eşleşmiyor.";
            } 
            elseif (strlen($new_password) < 6) {
                $error = "Yeni şifreniz en az 6 karakter olmalıdır.";
            } 
            else {
                $new_hashed_password = password_hash($new_password, PASSWORD_BCRYPT);
                $stmt_update = $db->prepare("UPDATE User SET password = ? WHERE id = ?");
                if ($stmt_update->execute([$new_hashed_password, $user_id])) {
                    $success = "Şifreniz başarıyla güncellendi!";
                } else {
                    $error = "Şifre güncellenirken bir hata oluştu.";
                }
            }
        } else {
            $error = "Mevcut şifrenizi yanlış girdiniz.";
        }
    }
}

include '../src/templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h1>Şifre Değiştir</h1>
        <div class="card mt-3">
            <div class="card-body">
                <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php else:  ?>
                    <form action="sifre_degistir.php" method="POST">
                        <div class="mb-3">
                            <label for="current_password" class="form-label">Mevcut Şifreniz</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="new_password" class="form-label">Yeni Şifre</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Şifreyi Güncelle</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>