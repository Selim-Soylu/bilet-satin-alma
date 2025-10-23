<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';
requireAuth(['admin', 'company']);
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

$db = getDBConnection();
$user_role = $_SESSION['role'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = strtoupper(trim($_POST['code']));
    $discount = $_POST['discount'];
    $usage_limit = $_POST['usage_limit'];
    $expire_date = $_POST['expire_date'];
    $company_id = ($user_role === 'company') ? $_SESSION['company_id'] : null;

    $stmt_check = $db->prepare("SELECT id FROM Coupons WHERE code = ?");
    $stmt_check->execute([$code]);
    if ($stmt_check->fetch()) {
        $error = "Bu kupon kodu zaten kullanılıyor.";
    } else {
        $couponId = generate_uuid();
        $sql = "INSERT INTO Coupons (id, code, discount, usage_limit, expire_date, company_id) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_insert = $db->prepare($sql);
        if ($stmt_insert->execute([$couponId, $code, $discount, $usage_limit, $expire_date, $company_id])) {
            $success = "Kupon başarıyla oluşturuldu.";
        } else {
            $error = "Kupon oluşturulurken bir hata oluştu.";
        }
    }
}

if ($user_role === 'admin') {
    $stmt_coupons = $db->prepare("SELECT c.*, bc.name AS company_name, (SELECT COUNT(id) FROM User_Coupons WHERE coupon_id = c.id) AS usage_count FROM Coupons c LEFT JOIN Bus_Company bc ON c.company_id = bc.id ORDER BY c.created_at DESC");
} else {
    $company_id = $_SESSION['company_id'];
    $stmt_coupons = $db->prepare("SELECT c.*, (SELECT COUNT(id) FROM User_Coupons WHERE coupon_id = c.id) AS usage_count FROM Coupons c WHERE c.company_id = ? ORDER BY c.created_at DESC");
    $stmt_coupons->bindParam(1, $company_id);
}
$stmt_coupons->execute();
$coupons = $stmt_coupons->fetchAll();

include '../src/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Kupon Yönetimi</h1>
    <?php if ($user_role === 'admin'): ?>
        <a href="admin_panel.php" class="btn btn-secondary">Admin Paneline Dön</a>
    <?php else: ?>
        <a href="company_panel.php" class="btn btn-secondary">Firma Paneline Dön</a>
    <?php endif; ?>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-header"><h4>Yeni Kupon Oluştur</h4></div>
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo htmlspecialchars(urldecode($error)); ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?php echo htmlspecialchars(urldecode($success)); ?></div><?php endif; ?>
        <form action="kupon_yonetim.php" method="POST">
             <div class="row">
                <div class="col-md-4 mb-3"><label for="code" class="form-label">Kupon Kodu</label><input type="text" class="form-control" name="code" required></div>
                <div class="col-md-2 mb-3"><label for="discount" class="form-label">İndirim Oranı (%)</label><input type="number" class="form-control" name="discount" min="1" max="100" required></div>
                <div class="col-md-2 mb-3"><label for="usage_limit" class="form-label">Kullanım Limiti</label><input type="number" class="form-control" name="usage_limit" min="1" required></div>
                <div class="col-md-4 mb-3"><label for="expire_date" class="form-label">Son Kullanma Tarihi</label><input type="date" class="form-control" name="expire_date" required></div>
            </div>
            <button type="submit" class="btn btn-success" name="add_coupon">Kuponu Oluştur</button>
        </form>
    </div>
</div>
<div class="card shadow-sm">
    <div class="card-header"><h4>Mevcut Kuponlar</h4></div>
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Kod</th>
                    <th>İndirim</th>
                    <th>Kullanım (Kullanılan/Limit)</th>
                    <th>Son Tarih</th>
                    <th>Durum</th>
                    <?php if ($user_role === 'admin'): ?><th>Firma</th><?php endif; ?>
                    <th class="text-end">İşlem</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($coupons as $coupon): ?>
                <?php
                    $is_expired = strtotime($coupon['expire_date']) < time();
                    $is_limit_reached = $coupon['usage_limit'] > 0 && ($coupon['usage_count'] >= $coupon['usage_limit']);
                    $is_manually_disabled = $coupon['usage_limit'] == 0;
                    $is_active = !$is_expired && !$is_limit_reached && !$is_manually_disabled;
                ?>
                <tr class="<?php if (!$is_active) echo 'table-secondary text-muted'; ?>">
                    <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                    <td>%<?php echo htmlspecialchars($coupon['discount']); ?></td>
                    <td><?php echo $coupon['usage_count']; ?> / <?php echo $coupon['usage_limit']; ?></td>
                    <td><?php echo date('d/m/Y', strtotime($coupon['expire_date'])); ?></td>
                    <td>
                        <?php if ($is_active): ?>
                            <span class="badge bg-success">Aktif</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Pasif</span>
                        <?php endif; ?>
                    </td>
                    <?php if ($user_role === 'admin'): ?>
                        <td><?php echo $coupon['company_name'] ? htmlspecialchars($coupon['company_name']) : '<span class="badge bg-info">Genel</span>'; ?></td>
                    <?php endif; ?>
                    <td class="text-end">
                        <?php if ($is_active): ?>
                            <a href="kupon_pasif_yap.php?id=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Bu kuponu pasif hale getirmek istediğinizden emin misiniz?');">Pasif Yap</a>
                        <?php else: ?>
                            <span>-</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>