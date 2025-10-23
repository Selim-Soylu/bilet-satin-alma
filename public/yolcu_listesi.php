<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';
requireAuth(['company']);

$db = getDBConnection();
$trip_id = $_GET['trip_id'] ?? null;
$company_id = $_SESSION['company_id'];
if (!$trip_id) { header("Location: company_panel.php"); exit(); }

$stmt_check = $db->prepare("SELECT departure_city, destination_city FROM Trips WHERE id = ? AND company_id = ?");
$stmt_check->execute([$trip_id, $company_id]);
$trip_info = $stmt_check->fetch();
if (!$trip_info) { header("Location: company_panel.php?error=Gecersiz islem."); exit(); }

$stmt = $db->prepare("SELECT t.id AS ticket_id, u.full_name, bs.seat_number FROM Tickets t JOIN User u ON t.user_id = u.id JOIN Booked_Seats bs ON bs.ticket_id = t.id WHERE t.trip_id = ? AND t.status = 'active' ORDER BY bs.seat_number ASC");
$stmt->execute([$trip_id]);
$passengers = $stmt->fetchAll();

include '../src/templates/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1>Yolcu Listesi</h1>
    <a href="company_panel.php" class="btn btn-secondary">Seferlere Geri Dön</a>
</div>
<h5>Sefer: <?php echo htmlspecialchars($trip_info['departure_city']) . ' &rarr; ' . htmlspecialchars($trip_info['destination_city']); ?></h5>

<div class="card mt-3">
    <div class="card-body">
        <?php if (empty($passengers)): ?>
            <div class="alert alert-info">Bu sefer için satılmış aktif bilet bulunmamaktadır.</div>
        <?php else: ?>
            <table class="table">
                <thead><tr><th>Koltuk No</th><th>Yolcu Adı Soyadı</th><th class="text-end">İşlem</th></tr></thead>
                <tbody>
                    <?php foreach ($passengers as $passenger): ?>
                    <tr>
                        <td><strong><?php echo $passenger['seat_number']; ?></strong></td>
                        <td><?php echo htmlspecialchars($passenger['full_name']); ?></td>
                        <td class="text-end">
                            <a href="company_bilet_iptal.php?ticket_id=<?php echo $passenger['ticket_id']; ?>&trip_id=<?php echo $trip_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu yolcunun biletini iptal etmek istediğinizden emin misiniz? Bilet ücreti yolcunun hesabına iade edilecektir.');">Bileti İptal Et</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
<?php include '../src/templates/footer.php'; ?>