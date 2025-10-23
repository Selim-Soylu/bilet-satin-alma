<?php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    $company_name = trim($_POST['company_name']);

    if (empty($company_name)) {
        $error = "Firma adı boş bırakılamaz.";
    } else {
        $stmt_check = $db->prepare("SELECT id FROM Bus_Company WHERE name = ?");
        $stmt_check->execute([$company_name]);
        if ($stmt_check->fetch()) {
            $error = "Bu isimde bir firma zaten mevcut.";
        } else {
            $companyId = generate_uuid();
            
           
            $stmt_insert = $db->prepare("INSERT INTO Bus_Company (id, name, created_at) VALUES (?, ?, ?)");
            
           
            $current_time = date('Y-m-d H:i:s');
            if ($stmt_insert->execute([$companyId, $company_name, $current_time])) {
                $success = "Firma başarıyla eklendi.";
            } else {
                $error = "Firma eklenirken bir hata oluştu.";
            }
           
        }
    }
}

$companies = $db->query("SELECT * FROM Bus_Company ORDER BY created_at DESC")->fetchAll();

include '../src/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Admin Paneli - Firma Yönetimi</h1>
    <a href="admin_panel.php" class="btn btn-secondary">Geri Dön</a>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h4>Yeni Firma Ekle</h4>
    </div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>
        <form action="admin_firma_yonetim.php" method="POST">
            <div class="input-group">
                <input type="text" class="form-control" name="company_name" placeholder="Firma Adı" required>
                <button class="btn btn-success" type="submit" name="add_company">Ekle</button>
            </div>
        </form>
    </div>
</div>


<div class="card">
    <div class="card-header">
        <h4>Mevcut Firmalar</h4>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Firma Adı</th>
                    <th>Oluşturulma Tarihi</th>
                    <th class="text-end">İşlemler</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($companies)): ?>
                    <tr>
                        <td colspan="3" class="text-center">Henüz eklenmiş firma yok.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($companies as $company): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($company['name']); ?></td>
                            <td>
                             <?php if (!empty($company['created_at'])): ?>
                             <?php echo date('d/m/Y H:i', strtotime($company['created_at'])); ?>
                             <?php else: ?>
                            <span class="text-muted">Tarih Belirtilmemiş</span>
                            <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="admin_firma_duzenle.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                <a href="admin_firma_sil.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu firmayı silmek istediğinizden emin misiniz? Bu firmaya ait tüm seferler ve biletler de silinebilir!');">Sil</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>