<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once '../src/database.php';
require_once '../src/helpers.php';

requireAuth(['admin']);

include '../src/templates/header.php';
?>

<h1>Admin Paneli</h1>
<p>Bu panelden otobüs firmalarını, firma yetkililerini ve genel kuponları yönetebilirsiniz.</p>

<div class="row mt-4 g-4">
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Firma Yönetimi</h5>
                <p class="card-text">Yeni otobüs firmaları ekleyin, mevcutları düzenleyin veya silin.</p>
                <a href="admin_firma_yonetim.php" class="btn btn-primary mt-auto">Firmaları Yönet</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Firma Yetkilisi Yönetimi</h5>
                <p class="card-text">Yeni firma yetkilileri ('company' rolü) oluşturun ve firmalara atayın.</p>
                <a href="admin_kullanici_yonetim.php" class="btn btn-primary mt-auto">Yetkilileri Yönet</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100 shadow-sm">
            <div class="card-body d-flex flex-column">
                <h5 class="card-title">Kupon Yönetimi</h5>
                <p class="card-text">Tüm firmalar için geçerli genel indirim kuponları oluşturun.</p>
                <a href="kupon_yonetim.php" class="btn btn-primary mt-auto">Kuponları Yönet</a>
            </div>
        </div>
    </div>
</div>

<?php include '../src/templates/footer.php'; ?>