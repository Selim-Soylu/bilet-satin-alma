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


$stmt = $db->prepare("DELETE FROM Bus_Company WHERE id = ?");
if ($stmt->execute([$company_id])) {
    header("Location: admin_firma_yonetim.php?success=Firma başarıyla silindi.");
} else {
    header("Location: admin_firma_yonetim.php?error=Firma silinirken bir hata oluştu.");
}
exit();
?>