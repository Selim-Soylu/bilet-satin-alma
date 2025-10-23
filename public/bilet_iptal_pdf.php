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

$db = getDBConnection();
$stmt = $db->prepare("SELECT t.*, u.full_name, tr.departure_city, tr.destination_city, tr.departure_time, c.name AS company_name, bs.seat_number FROM Tickets t JOIN User u ON t.user_id = u.id JOIN Trips tr ON t.trip_id = tr.id JOIN Bus_Company c ON tr.company_id = c.id JOIN Booked_Seats bs ON bs.ticket_id = t.id WHERE t.id = ? AND t.user_id = ? AND t.status = 'canceled'");
$stmt->execute([$ticket_id, $user_id]);
$ticket = $stmt->fetch();

if (!$ticket) { die("Hata: İptal edilmiş bilet bulunamadı veya bu belgeyi görüntüleme yetkiniz yok."); }

$ingilizce_aylar = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
$turkce_aylar = ['Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran', 'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık'];


class PDF_Rotate extends tFPDF { 
    var $angle = 0;
    function Rotate($angle, $x = -1, $y = -1) { if ($x == -1) $x = $this->x; if ($y == -1) $y = $this->y; if ($this->angle != 0) $this->_out('Q'); $this->angle = $angle; if ($angle != 0) { $angle *= M_PI / 180; $c = cos($angle); $s = sin($angle); $cx = $x * $this->k; $cy = ($this->h - $y) * $this->k; $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy)); } }
    function _endpage() { if ($this->angle != 0) { $this->angle = 0; $this->_out('Q'); } parent::_endpage(); }
}


$pdf = new PDF_Rotate('P', 'mm', 'A4');
$pdf->AddPage();


$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans.ttf', true);
$pdf->SetFont('DejaVu', '', 12); 


$pdf->SetFont('DejaVu', 'B', 50); 
$pdf->SetTextColor(255, 192, 203); 
$pdf->Rotate(45, 55, 190); 
$pdf->Text(55, 190, 'İPTAL EDİLMİŞTİR');
$pdf->Rotate(0);
$pdf->SetTextColor(0, 0, 0); 

$pdf->SetFont('DejaVu', 'B', 20);
$pdf->Cell(0, 15, $ticket['company_name'], 0, 1, 'C'); 

$pdf->SetFont('DejaVu', '', 14);
$pdf->Cell(0, 10, 'Bilet İptal Belgesi', 0, 1, 'C');
$pdf->Ln(15); 

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Yolcu Adı Soyadı:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket['full_name'], 0, 1); 

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
$pdf->SetFont('DejaVu', '', 12); 
$pdf->Cell(0, 10, $ticket['seat_number'], 0, 1);
$pdf->Ln(10); 

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(0, 10, 'İade Bilgisi', 0, 1); 
$pdf->SetFont('DejaVu', '', 12);
$iadeMesaji = number_format($ticket['total_price'], 2) . ' TL tutarındaki bilet ücreti hesabınıza iade edilmiştir.';
$pdf->MultiCell(0, 10, $iadeMesaji, 0, 'L');

$pdf->Output('D', 'iptal-belgesi-'.$ticket_id.'.pdf');
?>