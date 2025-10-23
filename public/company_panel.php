<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['company']);

if (!isset($_SESSION['company_id']) || empty($_SESSION['company_id'])) {
    include '../src/templates/header.php';
    echo "<div class='alert alert-danger'><strong>Hata:</strong> Hesabınız herhangi bir firmaya atanmamış.</div>";
    include '../src/templates/footer.php';
    exit();
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];

$stmt_trips = $db->prepare("SELECT tr.*, (SELECT COUNT(id) FROM Tickets WHERE trip_id = tr.id) AS ticket_count FROM Trips AS tr WHERE tr.company_id = ? ORDER BY tr.departure_time DESC");
$stmt_trips->execute([$company_id]);
$trips = $stmt_trips->fetchAll();

$stmt_company = $db->prepare("SELECT name FROM Bus_Company WHERE id = ?");
$stmt_company->execute([$company_id]);
$company_name = $stmt_company->fetchColumn();

include '../src/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <h2><?php echo htmlspecialchars($company_name); ?> - Yönetim Paneli</h2>
    <div>
        <a href="kupon_yonetim.php" class="btn btn-info">Kuponları Yönet</a>
        <a href="sefer_ekle.php" class="btn btn-success">Yeni Sefer Ekle</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header"><h4>Seferleriniz</h4></div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Kalkış</th>
                        <th>Varış</th>
                        <th>Kalkış Zamanı</th>
                        <th>Satılan Bilet</th>
                        <th class="text-end">İşlemler</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($trips as $trip): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($trip['departure_city']); ?></td>
                            <td><?php echo htmlspecialchars($trip['destination_city']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($trip['departure_time'])); ?></td>
                            <td><span class="badge bg-primary"><?php echo $trip['ticket_count']; ?></span></td>
                            <td class="text-end">
                                <a href="yolcu_listesi.php?trip_id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-primary">Yolcuları Gör</a>
                                <a href="sefer_duzenle.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-warning">Düzenle</a>
                                <?php if ($trip['ticket_count'] == 0): ?>
                                    <a href="sefer_sil.php?id=<?php echo $trip['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bu sefere hiç bilet satılmamış. Kalıcı olarak silmek istediğinizden emin misiniz?');">Sil</a>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-danger" disabled title="Bu sefere bilet satıldığı için silemezsiniz.">Sil</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>