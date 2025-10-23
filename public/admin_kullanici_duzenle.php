<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['admin']);

$db = getDBConnection();
$user_id = $_GET['id'] ?? null;
if (!$user_id) {
    header("Location: admin_kullanici_yonetim.php");
    exit();
}

$stmt = $db->prepare("SELECT * FROM User WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    die("Hata: Kullanıcı bulunamadı.");
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name']);
    $newEmail = trim($_POST['email']);
    $company_id = $_POST['company_id'];
    $originalEmail = $user['email']; 

    $performUpdate = true;
    

  
    if ($newEmail !== $originalEmail) {
        $stmt_check_email = $db->prepare("SELECT id FROM User WHERE email = ?");
        $stmt_check_email->execute([$newEmail]);

        if ($stmt_check_email->fetch()) {
            $error = "Girmeye çalıştığınız yeni e-posta adresi ('" . htmlspecialchars($newEmail) . "') zaten başka bir kullanıcı tarafından kullanılıyor.";
            $performUpdate = false; 
        }
    }

    if ($performUpdate) {
        $stmt_update = $db->prepare("UPDATE User SET full_name = ?, email = ?, company_id = ? WHERE id = ?");
        if ($stmt_update->execute([$fullName, $newEmail, $company_id, $user_id])) {
            header("Location: admin_kullanici_yonetim.php?success=Kullanıcı bilgileri başarıyla güncellendi.");
            exit();
        } else {
            $error = "Güncelleme sırasında bir veritabanı hatası oluştu.";
        }
    }
}

$companies = $db->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll();

include '../src/templates/header.php';
?>

<h1>Firma Yetkilisini Düzenle</h1>
<div class="card">
    <div class="card-body">
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form action="admin_kullanici_duzenle.php?id=<?php echo $user['id']; ?>" method="POST">
             <div class="mb-3">
                <label for="full_name" class="form-label">Ad Soyad</label>
                <input type="text" class="form-control" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">E-posta Adresi</label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="company_id" class="form-label">Atanacak Firma</label>
                <select class="form-select" name="company_id" required>
                    <?php foreach ($companies as $company): ?>
                        <option value="<?php echo $company['id']; ?>" <?php if ($user['company_id'] === $company['id']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($company['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <p class="form-text">Not: Bu ekrandan şifre değiştirilemez.</p>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            <a href="admin_kullanici_yonetim.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>