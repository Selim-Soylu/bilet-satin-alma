<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['company']);

$db = getDBConnection();
$trip_id = $_GET['id'] ?? null;
$company_id = $_SESSION['company_id']; 

if (!$trip_id) {
    header("Location: company_panel.php");
    exit();
}

$stmt = $db->prepare("SELECT * FROM Trips WHERE id = ? AND company_id = ?");
$stmt->execute([$trip_id, $company_id]);
$trip = $stmt->fetch();

if (!$trip) {
    header("Location: company_panel.php?error=Gecersiz islem veya yetkiniz yok.");
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = "UPDATE Trips SET 
                departure_city = ?, 
                destination_city = ?, 
                departure_time = ?, 
                arrival_time = ?, 
                price = ?, 
                capacity = ? 
            WHERE id = ? AND company_id = ?"; 
            
    $stmt_update = $db->prepare($sql);
    
    if ($stmt_update->execute([
        $_POST['departure_city'],
        $_POST['destination_city'],
        $_POST['departure_time'],
        $_POST['arrival_time'],
        $_POST['price'],
        $_POST['capacity'],
        $trip_id,
        $company_id
    ])) {
        header("Location: company_panel.php?success=Sefer başarıyla güncellendi.");
        exit();
    } else {
        $error = "Sefer güncellenirken bir hata oluştu.";
    }
}

include '../src/templates/header.php';
?>

<h2>Seferi Düzenle</h2>
<div class="card">
    <div class="card-body">
        <?php if (!empty($error)): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
        <form action="sefer_duzenle.php?id=<?php echo htmlspecialchars($trip['id']); ?>" method="POST">
            <div class="mb-3">
                <label for="departure_city" class="form-label">Kalkış Şehri</label>
                <input type="text" class="form-control" id="departure_city" name="departure_city" value="<?php echo htmlspecialchars($trip['departure_city']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="destination_city" class="form-label">Varış Şehri</label>
                <input type="text" class="form-control" id="destination_city" name="destination_city" value="<?php echo htmlspecialchars($trip['destination_city']); ?>" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="departure_time" class="form-label">Kalkış Zamanı</label>
                    <input type="datetime-local" class="form-control" id="departure_time" name="departure_time" value="<?php echo date('Y-m-d\TH:i', strtotime($trip['departure_time'])); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="arrival_time" class="form-label">Varış Zamanı</label>
                    <input type="datetime-local" class="form-control" id="arrival_time" name="arrival_time" value="<?php echo date('Y-m-d\TH:i', strtotime($trip['arrival_time'])); ?>" required>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="price" class="form-label">Fiyat (TL)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" value="<?php echo htmlspecialchars($trip['price']); ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="capacity" class="form-label">Koltuk Kapasitesi</label>
                    <input type="number" class="form-control" id="capacity" name="capacity" value="<?php echo htmlspecialchars($trip['capacity']); ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary">Değişiklikleri Kaydet</button>
            <a href="company_panel.php" class="btn btn-secondary">İptal</a>
        </form>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>