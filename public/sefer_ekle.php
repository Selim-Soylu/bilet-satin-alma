<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['company']);

function generate_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDBConnection();
    
    $sql = "INSERT INTO Trips (id, departure_city, destination_city, departure_time, arrival_time, price, capacity, company_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $db->prepare($sql);
    
    $tripId = generate_uuid();
    $company_id = $_SESSION['company_id'];
    
    if ($stmt->execute([
        $tripId,
        $_POST['departure_city'],
        $_POST['destination_city'],
        $_POST['departure_time'],
        $_POST['arrival_time'],
        $_POST['price'],
        $_POST['capacity'],
        $company_id
    ])) {
        header("Location: company_panel.php?success=Sefer başarıyla eklendi.");
        exit();
    } else {
        $error = "Sefer eklenirken bir hata oluştu.";
    }
}

include '../src/templates/header.php';
?>

<h2>Yeni Sefer Ekle</h2>
<div class="card">
    <div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form action="sefer_ekle.php" method="POST">
            <div class="mb-3">
                <label for="departure_city" class="form-label">Kalkış Şehri</label>
                <input type="text" class="form-control" id="departure_city" name="departure_city" required>
            </div>
            <div class="mb-3">
                <label for="destination_city" class="form-label">Varış Şehri</label>
                <input type="text" class="form-control" id="destination_city" name="destination_city" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="departure_time" class="form-label">Kalkış Zamanı</label>
                    <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="arrival_time" class="form-label">Varış Zamanı</label>
                    <input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="price" class="form-label">Fiyat (TL)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="capacity" class="form-label">Koltuk Kapasitesi</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Seferi Kaydet</button>
            <a href="company_panel.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>