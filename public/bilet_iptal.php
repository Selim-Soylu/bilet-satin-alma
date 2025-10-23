<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['user']);

$ticket_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$ticket_id) {
    header('Location: hesabim.php?error=Geçersiz bilet ID.');
    exit();
}

$db = getDBConnection();

try {
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT t.status, t.total_price, t.user_id, tr.departure_time 
        FROM Tickets AS t 
        JOIN Trips AS tr ON t.trip_id = tr.id 
        WHERE t.id = ?
    ");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();

    if (!$ticket) {
        throw new Exception("Bilet bulunamadı.");
    }
    
    if ($ticket['user_id'] !== $user_id) {
        throw new Exception("Bu bileti iptal etme yetkiniz yok.");
    }
    
    if ($ticket['status'] !== 'active') {
        throw new Exception("Bu bilet zaten aktif değil, iptal edilemez.");
    }

    $departure_timestamp = strtotime($ticket['departure_time']);
    if (($departure_timestamp - time()) <= 3600) {
        throw new Exception("Seferin kalkışına bir saatten az kaldığı için bilet iptal edilemez.");
    }

    $stmt = $db->prepare("UPDATE Tickets SET status = 'canceled' WHERE id = ?");
    $stmt->execute([$ticket_id]);

    $stmt = $db->prepare("UPDATE User SET balance = balance + ? WHERE id = ?");
    $stmt->execute([$ticket['total_price'], $user_id]);

    $db->commit();
    
    header('Location: hesabim.php?success=Bilet başarıyla iptal edildi ve ücret iade edildi.');
    exit();

} catch (Exception $e) {
    $db->rollBack();
    die("Bilet iptali sırasında bir hata oluştu: " . $e->getMessage() . " <a href='hesabim.php'>Geri Dön</a>");
}
?>