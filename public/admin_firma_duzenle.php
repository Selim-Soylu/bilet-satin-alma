<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['admin']);

$db = getDBConnection();
$company_id = $_GET['id'] ?? null;
if (!$company_id) {
    header("Location: admin_firma_yonetim.php");
    exit();
}

$stmt = $db->prepare("SELECT * FROM Bus_Company WHERE id = ?");
$stmt->execute([$company_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: admin_firma_yonetim.php?error=Firma bulunamadı.");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_name = trim($_POST['company_name']);
    if (empty($new_name)) {
        $error = "Firma adı boş bırakılamaz.";
    } else {
        $stmt_update = $db->prepare("UPDATE Bus_Company SET name = ? WHERE id = ?");
        if ($stmt_update->execute([$new_name, $company_id])) {
            header("Location: admin_firma_yonetim.php?success=Firma başarıyla güncellendi.");
            exit();
        } else {
            $error = "Firma güncellenirken bir hata oluştu.";
        }
    }
}

include '../src/templates/header.php';
?>

<h1>Firma Düzenle</h1>
<div class="card">
    <div class="card-body">
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form action="admin_firma_duzenle.php?id=<?php echo $company['id']; ?>" method="POST">
            <div class="mb-3">
                <label for="company_name" class="form-label">Firma Adı</label>
                <input type="text" class="form-control" id="company_name" name="company_name" value="<?php echo htmlspecialchars($company['name']); ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            <a href="admin_firma_yonetim.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>