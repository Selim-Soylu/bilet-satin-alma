<?php
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

$stmt = $db->prepare("DELETE FROM User WHERE id = ? AND role = 'company'");
if ($stmt->execute([$user_id])) {
    header("Location: admin_kullanici_yonetim.php?success=Kullanıcı başarıyla silindi.");
} else {
    header("Location: admin_kullanici_yonetim.php?error=Kullanıcı silinirken bir hata oluştu.");
}
exit();
?>