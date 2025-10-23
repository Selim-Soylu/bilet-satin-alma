<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';
requireAuth(['company']); 

$db = getDBConnection();
$trip_id = $_GET['id'] ?? null;
$company_id = $_SESSION['company_id'];
if (!$trip_id) { header("Location: company_panel.php"); exit(); }

try {
    $stmt_tickets = $db->prepare("SELECT id FROM Tickets WHERE trip_id = ? LIMIT 1");
    $stmt_tickets->execute([$trip_id]);
    if ($stmt_tickets->fetch()) {
        throw new Exception("Bu sefere daha önce bilet satıldığı için silemezsiniz.");
    }

    $stmt_delete = $db->prepare("DELETE FROM Trips WHERE id = ? AND company_id = ?");
    $stmt_delete->execute([$trip_id, $company_id]);
    
    if ($stmt_delete->rowCount() > 0) {
        header("Location: company_panel.php?success=Sefer başarıyla silindi.");
    } else {
        throw new Exception("Sefer bulunamadı veya silme yetkiniz yok.");
    }
    exit();

} catch (Exception $e) {
    header("Location: company_panel.php?error=" . urlencode($e->getMessage()));
    exit();
}
?>