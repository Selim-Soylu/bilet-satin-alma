<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['user']); 

$db = getDBConnection();
$user_id = $_SESSION['user_id'];

$success_message = null;
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

$stmt_user = $db->prepare("SELECT full_name, balance FROM User WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

$stmt_tickets = $db->prepare("SELECT t.id AS ticket_id, t.status, t.total_price, tr.departure_city, tr.destination_city, tr.departure_time, c.name AS company_name, bs.seat_number FROM Tickets AS t JOIN Trips AS tr ON t.trip_id = tr.id JOIN Bus_Company AS c ON tr.company_id = c.id JOIN Booked_Seats AS bs ON bs.ticket_id = t.id WHERE t.user_id = ? ORDER BY tr.departure_time DESC");
$stmt_tickets->execute([$user_id]);
$tickets = $stmt_tickets->fetchAll();

include '../src/templates/header.php';
?>

<h2>Hesabım</h2>

<?php if ($success_message): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-body">
        <p class="mb-1"><strong>Ad Soyad:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
        <p class="mb-0"><strong>Mevcut Bakiye:</strong> <span class="fw-bold text-success"><?php echo number_format($user['balance'], 2); ?> TL</span></p>
    </div>
</div>

<h3>Biletlerim</h3>
<?php if (empty($tickets)): ?>
    <div class="alert alert-info">Henüz satın alınmış biletiniz bulunmamaktadır.</div>
<?php else: ?>
    <div class="list-group">
    <?php foreach ($tickets as $ticket): ?>
        <div class="list-group-item mb-3 shadow-sm">
            <div class="d-flex w-100 justify-content-between">
                 <h5 class="mb-1"><?php echo htmlspecialchars($ticket['departure_city']); ?> &rarr; <?php echo htmlspecialchars($ticket['destination_city']); ?></h5>
                <small>Koltuk No: <strong><?php echo $ticket['seat_number']; ?></strong></small>
            </div>
            <p class="mb-1">
                <strong>Firma:</strong> <?php echo htmlspecialchars($ticket['company_name']); ?><br>
                <strong>Tarih:</strong> <?php echo date('d/m/Y H:i', strtotime($ticket['departure_time'])); ?><br>
                <strong>Durum:</strong> 
                <?php 
                    $status_class = 'bg-secondary';
                    if ($ticket['status'] === 'active') $status_class = 'bg-success';
                    if ($ticket['status'] === 'canceled') $status_class = 'bg-danger';
                ?>
                <span class="badge <?php echo $status_class; ?>"><?php echo htmlspecialchars(ucfirst($ticket['status'])); ?></span>
            </p>
            
            <div class="mt-2">
                <?php if ($ticket['status'] === 'active'): ?>
                    <?php
                    $departureDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $ticket['departure_time']);
                    if (!$departureDateTime) {
                         $departureDateTime = DateTime::createFromFormat('Y-m-d H:i', $ticket['departure_time']);
                    }
                    
                    $currentDateTime = new DateTime(); 

                    $can_cancel = false; 
                    if ($departureDateTime && $currentDateTime) {
                        $time_difference = $departureDateTime->getTimestamp() - $currentDateTime->getTimestamp();
                        $can_cancel = ($time_difference > 3600);
                    }
                    ?>
                    <?php if ($can_cancel): ?>
                        <a href="bilet_iptal.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu bileti iptal etmek istediğinizden emin misiniz?');">Bileti İptal Et</a>
                    <?php endif; ?>
                    <a href="bilet_pdf.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-info" target="_blank">Bilet PDF İndir</a>

                <?php elseif ($ticket['status'] === 'canceled'): ?>
                    <a href="bilet_iptal_pdf.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-secondary" target="_blank">İptal Belgesi İndir</a>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include '../src/templates/footer.php'; ?>