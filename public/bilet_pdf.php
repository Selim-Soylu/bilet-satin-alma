<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once '../libs/tfpdf.php'; 
require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['user']);

$ticket_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];
if (!$ticket_id) { die("Hata: Geçersiz bilet ID'si."); }

$ingilizce_aylar = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$turkce_aylar = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];

$db = getDBConnection();
$stmt = $db->prepare("
    SELECT t.id AS ticket_id, t.total_price, t.created_at AS purchase_date, u.full_name AS user_name,
           tr.departure_city, tr.destination_city, tr.departure_time, c.name AS company_name, bs.seat_number
    FROM Tickets AS t
    JOIN User AS u ON t.user_id = u.id JOIN Trips AS tr ON t.trip_id = tr.id
    JOIN Bus_Company AS c ON tr.company_id = c.id JOIN Booked_Seats AS bs ON bs.ticket_id = t.id
    WHERE t.id = ? AND t.user_id = ?
");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) { die("Hata: Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok."); }

$pdf = new tFPDF('P', 'mm', 'A4'); 
$pdf->AddPage();

$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true); 
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans.ttf', true); 
$pdf->AddFont('DejaVu', 'I', 'DejaVuSans.ttf', true);
$pdf->SetFont('DejaVu', '', 12); 

$pdf->SetFont('DejaVu', 'B', 20); 
$pdf->Cell(0, 15, $ticket['company_name'], 0, 1, 'C'); 

$pdf->SetFont('DejaVu', '', 14);
$pdf->Cell(0, 10, 'Yolcu Seyahat Bileti', 0, 1, 'C'); 
$pdf->Ln(15); 

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Yolcu Adı Soyadı:', 0, 0); 
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket['user_name'], 0, 1);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Güzergah:', 0, 0); 
$pdf->SetFont('DejaVu', '', 12);
$guzergah = $ticket['departure_city'] . ' -> ' . $ticket['destination_city'];
$pdf->Cell(0, 10, $guzergah, 0, 1); 

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Kalkış Zamanı:', 0, 0); 
$pdf->SetFont('DejaVu', '', 12);
$timestamp = strtotime($ticket['departure_time']);
$ingilizce_tarih = date('d F Y', $timestamp);
$turkce_tarih = str_replace($ingilizce_aylar, $turkce_aylar, $ingilizce_tarih);
$timePart = date('H:i', $timestamp); 
$fullTurkishDateTime = $turkce_tarih . ', ' . $timePart;
$pdf->Cell(0, 10, $fullTurkishDateTime, 0, 1); 

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Koltuk Numarası:', 0, 0); 
$pdf->SetFont('DejaVu', 'B', 12); 
$pdf->Cell(0, 10, $ticket['seat_number'], 0, 1);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Ödenen Tutar:', 0, 0); 
$pdf->SetFont('Arial', '', 12); 
$pdf->Cell(0, 10, number_format($ticket['total_price'], 2) . ' TL', 0, 1);
$pdf->Ln(20); 

$pdf->SetFont('DejaVu', 'I', 10); 
$pdf->Cell(0, 10, 'İyi yolculuklar dileriz!', 0, 1, 'C'); 

$pdf->Output('D', 'soylu-seyahat-bilet-'.$ticket_id.'.pdf');
?>