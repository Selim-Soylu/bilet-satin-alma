<?php
session_start();
require_once '../src/database.php';
require_once '../src/helpers.php';
requireAuth(['admin', 'company']);

$db = getDBConnection();
$coupon_id = $_GET['id'] ?? null;
if (!$coupon_id) { header("Location: kupon_yonetim.php"); exit(); }

try {
    if ($_SESSION['role'] === 'admin') {
        $stmt = $db->prepare("UPDATE Coupons SET usage_limit = 0 WHERE id = ?");
        $params = [$coupon_id];
    } else {
        $company_id = $_SESSION['company_id'];
        $stmt = $db->prepare("UPDATE Coupons SET usage_limit = 0 WHERE id = ? AND company_id = ?");
        $params = [$coupon_id, $company_id];
    }
    
    $stmt->execute($params);
    
    if ($stmt->rowCount() > 0) {
        header("Location: kupon_yonetim.php?success=Kupon başarıyla pasif hale getirildi.");
    } else {
        throw new Exception("Geçersiz işlem veya bu kuponu pasif yapma yetkiniz yok.");
    }

} catch (Exception $e) {
    header("Location: kupon_yonetim.php?error=" . urlencode($e->getMessage()));
}
exit();
?>