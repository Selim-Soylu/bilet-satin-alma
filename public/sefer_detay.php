<?php
session_start();
ini_set('display_errors', 1); error_reporting(E_ALL);
require_once '../src/database.php';
require_once '../src/helpers.php';

$error_message = null;
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

$trip_id = $_GET['id'] ?? null;
if (!$trip_id) { header('Location: index.php'); exit(); }

$db = getDBConnection();
$stmt = $db->prepare("SELECT tr.*, bc.name AS company_name FROM Trips AS tr JOIN Bus_Company AS bc ON tr.company_id = bc.id WHERE tr.id = ?");
$stmt->execute([$trip_id]);
$trip = $stmt->fetch();
if (!$trip) { die("Hata: Belirtilen sefer bulunamadı."); }

$stmt_seats = $db->prepare("SELECT bs.seat_number FROM Booked_Seats AS bs JOIN Tickets AS t ON bs.ticket_id = t.id WHERE t.trip_id = ? AND t.status = 'active'");
$stmt_seats->execute([$trip_id]);
$taken_seats = $stmt_seats->fetchAll(PDO::FETCH_COLUMN);

include '../src/templates/header.php';
?>

<h2>Sefer Detayları ve Koltuk Seçimi</h2>

<?php if ($error_message): ?>
    <div class="alert alert-danger" role="alert">
        <strong>Hata:</strong> <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<div class="card mb-4">
    <div class="card-header">
        <h4><?php echo htmlspecialchars($trip['departure_city']); ?> &rarr; <?php echo htmlspecialchars($trip['destination_city']); ?></h4>
    </div>
    <div class="card-body">
        <p><strong>Firma:</strong> <?php echo htmlspecialchars($trip['company_name']); ?></p>
        <p><strong>Tarih:</strong> <?php echo date('d/m/Y H:i', strtotime($trip['departure_time'])); ?></p>
        <p><strong>Fiyat:</strong> <?php echo htmlspecialchars($trip['price']); ?> TL</p>
    </div>
</div>

<div class="card">
    <div class="card-header">Koltuk Seçin ve Kupon Girin</div>
    <div class="card-body">
        <form action="bilet_al.php" method="POST">
            <input type="hidden" name="trip_id" value="<?php echo $trip['id']; ?>">
            <input type="hidden" name="price" value="<?php echo $trip['price']; ?>">
            
            <h5>1. Adım: Koltuk Seçimi</h5>
            <div class="row">
                <?php for ($i = 1; $i <= $trip['capacity']; $i++): 
                    $is_taken = in_array($i, $taken_seats);
                ?>
                    <div class="col-2 col-md-1 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="seat_number" id="seat<?php echo $i; ?>" value="<?php echo $i; ?>" <?php if($is_taken) echo 'disabled'; ?>>
                            <label class="form-check-label p-2 border text-center <?php echo $is_taken ? 'bg-danger text-white' : 'bg-light'; ?>" for="seat<?php echo $i; ?>"><?php echo $i; ?></label>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
            <hr>
            
            <h5>2. Adım: İndirim Kuponu (İsteğe Bağlı)</h5>
             <div class="row">
                <div class="col-md-4">
                    <label for="coupon_code" class="form-label">Kupon Kodunuz Varsa Girin</label>
                    <input type="text" class="form-control" name="coupon_code" id="coupon_code" placeholder="Örn: HOSGELDIN20">
                </div>
            </div>
            <hr>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'user'): ?>
                <button type="submit" class="btn btn-success btn-lg">Bileti Satın Al</button>
            <?php elseif (!isset($_SESSION['user_id'])): ?>
                <div class="alert alert-info">Bilet satın almak için <a href="login.php">lütfen giriş yapın</a>.</div>
            <?php else: ?>
                 <div class="alert alert-warning">Sadece 'Yolcu' rolündeki kullanıcılar bilet satın alabilir.</div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>