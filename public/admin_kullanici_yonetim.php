<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['admin']);

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

$db = getDBConnection();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullName = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $company_id = $_POST['company_id'];

    if (empty($fullName) || empty($email) || empty($password) || empty($company_id)) {
        $error = "Tüm alanlar zorunludur.";
    } else {
        $stmt_check = $db->prepare("SELECT id FROM User WHERE email = ?");
        $stmt_check->execute([$email]);
        if ($stmt_check->fetch()) {
            $error = "Bu e-posta adresi zaten başka bir kullanıcı tarafından kullanılıyor.";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $userId = generate_uuid();
            
            $sql = "INSERT INTO User (id, full_name, email, password, role, company_id) VALUES (?, ?, ?, ?, 'company', ?)";
            $stmt_insert = $db->prepare($sql);
            
            if ($stmt_insert->execute([$userId, $fullName, $email, $hashedPassword, $company_id])) {
                $success = "Firma yetkilisi başarıyla eklendi.";
            } else {
                $error = "Kullanıcı eklenirken bir veritabanı hatası oluştu.";
            }
        }
    }
}

$companies = $db->query("SELECT id, name FROM Bus_Company ORDER BY name ASC")->fetchAll();

$stmt_users = $db->prepare("
    SELECT u.id, u.full_name, u.email, bc.name AS company_name 
    FROM User AS u
    LEFT JOIN Bus_Company AS bc ON u.company_id = bc.id
    WHERE u.role = 'company'
    ORDER BY u.created_at DESC
");
$stmt_users->execute();
$company_users = $stmt_users->fetchAll();

include '../src/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Admin Paneli - Firma Yetkilisi Yönetimi</h1>
    <a href="admin_panel.php" class="btn btn-secondary">Geri Dön</a>
</div>

<div class="card mb-4">
    <div class="card-header"><h4>Yeni Firma Yetkilisi Ekle</h4></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form action="admin_kullanici_yonetim.php" method="POST">
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="full_name" class="form-label">Ad Soyad</label>
                    <input type="text" class="form-control" name="full_name" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="company_id" class="form-label">Atanacak Firma</label>
                    <select class="form-select" name="company_id" required>
                        <option value="" selected disabled>Lütfen bir firma seçin...</option>
                        <?php foreach ($companies as $company): ?>
                            <option value="<?php echo $company['id']; ?>"><?php echo htmlspecialchars($company['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="email" class="form-label">E-posta Adresi</label>
                    <input type="email" class="form-control" name="email" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Geçici Şifre</label>
                    <input type="password" class="form-control" name="password" required>
                </div>
            </div>
            <button class="btn btn-success" type="submit" name="add_user">Yetkiliyi Ekle</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><h4>Mevcut Firma Yetkilileri</h4></div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Ad Soyad</th>
                    <th>E-posta</th>
                    <th>Atandığı Firma</th>
                    <th class="text-end">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($company_users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td>
                            <?php if ($user['company_name']): ?>
                                <?php echo htmlspecialchars($user['company_name']); ?>
                            <?php else: ?>
                                <span class="text-danger">Atanmamış</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a href="admin_kullanici_duzenle.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                            <a href="admin_kullanici_sil.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Sil</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>