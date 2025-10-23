<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';
requireAuth(['user']);
function generate_uuid() { return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)); }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php'); exit(); }

$trip_id = $_POST['trip_id'] ?? null;
$seat_number = $_POST['seat_number'] ?? null;
$price = (float)($_POST['price'] ?? 0);
$coupon_code = strtoupper(trim($_POST['coupon_code'] ?? ''));
$user_id = $_SESSION['user_id'];
$final_price = $price; 
$valid_coupon_id = null; 

if (empty($seat_number)) { $_SESSION['error_message'] = "Hata: Lütfen bir koltuk seçin."; header("Location: sefer_detay.php?id=" . $trip_id); exit(); }
if (empty($trip_id) || empty($price)) { $_SESSION['error_message'] = "Hata: Sefer bilgileri eksik."; header("Location: index.php"); exit(); }

$db = getDBConnection();

try {
    $db->beginTransaction();

    if (!empty($coupon_code)) {
        $stmt_trip_company = $db->prepare("SELECT company_id FROM Trips WHERE id = ?");
        $stmt_trip_company->execute([$trip_id]);
        $trip_company_id = $stmt_trip_company->fetchColumn();

        $stmt_coupon = $db->prepare("SELECT * FROM Coupons WHERE code = ?");
        $stmt_coupon->execute([$coupon_code]);
        $coupon = $stmt_coupon->fetch();

        if (!$coupon) { throw new Exception("Geçersiz kupon kodu girdiniz."); }
        if (strtotime($coupon['expire_date']) < time()) { throw new Exception("Bu kuponun süresi dolmuş."); }
        if ($coupon['company_id'] !== null && $coupon['company_id'] !== $trip_company_id) { throw new Exception("Bu kupon bu firma için geçerli değil."); }
        
        $stmt_usage = $db->prepare("SELECT COUNT(id) FROM User_Coupons WHERE coupon_id = ?");
        $stmt_usage->execute([$coupon['id']]);
        $usage_count = $stmt_usage->fetchColumn();
        if ($usage_count >= $coupon['usage_limit']) { throw new Exception("Bu kupon kullanım limitine ulaşmış."); }

        $discount_amount = ($price * $coupon['discount']) / 100;
        $final_price = $price - $discount_amount;
        $valid_coupon_id = $coupon['id']; 
    }


    $stmt_check_seat = $db->prepare("SELECT t.id FROM Tickets t JOIN Booked_Seats bs ON t.id = bs.ticket_id WHERE t.trip_id = ? AND bs.seat_number = ? AND t.status = 'active'");
    $stmt_check_seat->execute([$trip_id, $seat_number]);
    if ($stmt_check_seat->fetch()) { throw new Exception("Seçtiğiniz koltuk siz işlem yaparken başkası tarafından satın alındı."); }

    $stmt_balance = $db->prepare("SELECT balance FROM User WHERE id = ?");
    $stmt_balance->execute([$user_id]);
    $user_balance = $stmt_balance->fetchColumn();
    if ($user_balance < $final_price) { throw new Exception("Yetersiz bakiye. Gerekli tutar: " . number_format($final_price, 2) . " TL"); }

    $stmt_update_balance = $db->prepare("UPDATE User SET balance = balance - ? WHERE id = ?");
    $stmt_update_balance->execute([$final_price, $user_id]);

    $ticketId = generate_uuid();
    $stmt_ticket = $db->prepare("INSERT INTO Tickets (id, status, total_price, trip_id, user_id) VALUES (?, 'active', ?, ?, ?)");
    $stmt_ticket->execute([$ticketId, $final_price, $trip_id, $user_id]);
    
    $bookedSeatId = generate_uuid();
    $stmt_seat = $db->prepare("INSERT INTO Booked_Seats (id, seat_number, ticket_id) VALUES (?, ?, ?)");
    $stmt_seat->execute([$bookedSeatId, $seat_number, $ticketId]);
    
    if ($valid_coupon_id) {
        $userCouponId = generate_uuid();
        $stmt_log_coupon = $db->prepare("INSERT INTO User_Coupons (id, coupon_id, user_id) VALUES (?, ?, ?)");
        $stmt_log_coupon->execute([$userCouponId, $valid_coupon_id, $user_id]);
    }

    $db->commit();
    
    $success_message = "Biletiniz başarıyla satın alındı!";
    if($valid_coupon_id) { $success_message .= " İndirimli Tutar: " . number_format($final_price, 2) . " TL"; }
    $_SESSION['success_message'] = $success_message;
    header('Location: hesabim.php');
    exit();

} catch (Exception $e) {
    $db->rollBack();
    $_SESSION['error_message'] = $e->getMessage();
    header("Location: sefer_detay.php?id=" . $trip_id);
    exit();
}
?>