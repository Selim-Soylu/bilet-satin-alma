<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';
requireAuth(['company']);

$ticket_id = $_GET['ticket_id'] ?? null;
$trip_id = $_GET['trip_id'] ?? null; 
$company_id = $_SESSION['company_id'];
if (!$ticket_id || !$trip_id) { header("Location: company_panel.php"); exit(); }

$db = getDBConnection();
try {
    $db->beginTransaction();

    $stmt_check = $db->prepare("SELECT t.status, t.total_price, t.user_id FROM Tickets t JOIN Trips tr ON t.trip_id = tr.id WHERE t.id = ? AND tr.company_id = ?");
    $stmt_check->execute([$ticket_id, $company_id]);
    $ticket = $stmt_check->fetch();
    if (!$ticket) { throw new Exception("Geçersiz işlem veya bu bileti iptal etme yetkiniz yok."); }
    if ($ticket['status'] !== 'active') { throw new Exception("Bu bilet zaten aktif değil."); }

    $stmt_cancel = $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
    $stmt_cancel->execute([$ticket_id]);

    $stmt_refund = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt_refund->execute([$ticket['total_price'], $ticket['user_id']]);

    $db->commit();
    header("Location: yolcu_listesi.php?trip_id=" . $trip_id . "&success=Bilet başarıyla iptal edildi ve ücret yolcuya iade edildi.");
    exit();

} catch (Exception $e) {
    $db->rollBack();
    header("Location: yolcu_listesi.php?trip_id=" . $trip_id . "&error=" . urlencode($e->getMessage()));
    exit();
}
?>