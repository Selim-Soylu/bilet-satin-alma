<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start(); 


require_once '../src/database.php';
require_once '../src/data/iller.php'; 

$subtitles = ["Türkiye'nin dört bir yanına en konforlu yolculuklar.", "Türkiye'nin dört bir yanına en uygun fiyatlı yolculuklar.", "Türkiye'nin dört bir yanına en keyifli yolculuklar."];
$random_subtitle = $subtitles[array_rand($subtitles)];

$trips = []; $search_performed = false; $departure_city = ''; $destination_city = ''; $start_date = ''; $end_date = ''; $db_error = null; $search_error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search_performed = true; $departure_city = trim($_POST['departure_city'] ?? ''); $destination_city = trim($_POST['destination_city'] ?? ''); $start_date = $_POST['start_date'] ?? null; $end_date = $_POST['end_date'] ?? null;
    if (!empty($departure_city) && !empty($destination_city)) {
        try {
            $db = getDBConnection();
            $sql = "SELECT tr.*, bc.name AS company_name FROM Trips AS tr JOIN Bus_Company AS bc ON tr.company_id = bc.id WHERE LOWER(tr.departure_city) = LOWER(:departure) AND LOWER(tr.destination_city) = LOWER(:destination)";
            $sql .= " AND tr.departure_time > DATETIME('now')";
            $params = [':departure' => $departure_city, ':destination' => $destination_city];
            if (!empty($start_date)) { $sql .= " AND DATE(tr.departure_time) >= DATE(:start_date)"; $params[':start_date'] = $start_date; }
            if (!empty($end_date)) { $sql .= " AND DATE(tr.departure_time) <= DATE(:end_date)"; $params[':end_date'] = $end_date; }
            $sql .= " ORDER BY tr.departure_time ASC";
            $stmt = $db->prepare($sql); $stmt->execute($params); $trips = $stmt->fetchAll();
        } catch (PDOException $e) { $db_error = "Veritabanı hatası: " . $e->getMessage(); }
    } else { $search_error = "Lütfen kalkış ve varış şehirlerini girin."; }
}

$iller_json = json_encode($turkiyeIlleri);

include '../src/templates/header.php'; 
?>

<?php if ($db_error): ?><div class="alert alert-danger"><?php echo $db_error; ?></div><?php endif; ?>
<?php if ($search_error): ?><div class="alert alert-warning"><?php echo $search_error; ?></div><?php endif; ?>

<div class="p-5 mb-4 bg-light rounded-3 text-center"><div class="container-fluid py-5"><h1 class="display-5 fw-bold">Nereye Seyahat Etmek İstersiniz?</h1><p class="fs-4"><?php echo htmlspecialchars($random_subtitle); ?></p></div></div>

<div class="row justify-content-center"><div class="col-lg-10"><div class="card shadow-sm"><div class="card-body">
<h2 class="card-title text-center mb-4">Sefer Arama</h2>
<form action="index.php" method="POST"><div class="row g-3 align-items-end">
    <div class="col-md-3"><label for="departure_city" class="form-label">Kalkış Şehri</label><input type="text" class="form-control" id="departure_city" name="departure_city" value="<?php echo htmlspecialchars($departure_city); ?>" required></div>
    <div class="col-md-3"><label for="destination_city" class="form-label">Varış Şehri</label><input type="text" class="form-control" id="destination_city" name="destination_city" value="<?php echo htmlspecialchars($destination_city); ?>" required></div>
    <div class="col-md-2"><label for="start_date" class="form-label">Başlangıç Tarihi</label><input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>"></div>
    <div class="col-md-2"><label for="end_date" class="form-label">Bitiş Tarihi</label><input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>"></div>
    <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Sefer Bul</button></div>
</div></form>
</div></div></div></div>

<?php if ($search_performed && !$db_error && !$search_error): ?>
<div class="mt-5">
    <h3 class="text-center mb-4">Arama Sonuçları</h3>
    <?php if (empty($trips)): ?> 
        <div class="alert alert-warning text-center" role="alert">Belirtilen kriterlere uygun sefer bulunamadı.</div>
    <?php else: ?> 
        <div class="list-group">
            <?php foreach ($trips as $trip): ?>
            <a href="sefer_detay.php?id=<?php echo $trip['id']; ?>" class="list-group-item list-group-item-action mb-3 shadow-sm">
                <div class="row align-items-center">
                    <div class="col-md-5">
                        <h5 class="mb-1"><?php echo htmlspecialchars($trip['departure_city']); ?> &rarr; <?php echo htmlspecialchars($trip['destination_city']); ?></h5>
                        <small>Firma: <?php echo htmlspecialchars($trip['company_name']); ?></small>
                    </div>
                    <div class="col-md-3 text-md-center">
                        <small><strong><?php echo date('d/m/Y H:i', strtotime($trip['departure_time'])); ?></strong></small>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <strong class="text-success fs-5"><?php echo htmlspecialchars($trip['price']); ?> TL</strong>
                    </div>
                    <div class="col-md-2 text-md-end">
                        <span class="btn btn-sm btn-outline-primary">Koltuk Seç</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div> 
    <?php endif; ?>
</div> 
<?php endif; ?>
<?php include '../src/templates/footer.php'; ?>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        var iller = <?php echo $iller_json; ?>; 
        var inputKalkis = document.getElementById("departure_city");
        var inputVaris = document.getElementById("destination_city");
        var aweOptions = {
            list: iller, minChars: 1, maxItems: 10,
            filter: function (text, input) {
                const currentInput = input.match(/[^,]*$/)[0];
                return text.toLocaleLowerCase('tr-TR').indexOf(currentInput.toLocaleLowerCase('tr-TR')) !== -1;
            },
            item: function (text, input) {
                const currentInput = input.match(/[^,]*$/)[0];
                const lowerText = text.toLocaleLowerCase('tr-TR');
                const lowerInput = currentInput.toLocaleLowerCase('tr-TR');
                const index = lowerText.indexOf(lowerInput);
                if (index === -1) { return Awesomplete.ITEM(text, currentInput); }
                 const marked = text.substring(0, index) + '<mark>' + text.substring(index, index + currentInput.length) + '</mark>' + text.substring(index + currentInput.length);
                 const li = document.createElement("li");
                 li.innerHTML = marked; li.setAttribute("role", "option"); li.setAttribute("aria-selected", "false");
                 return li;
            },
             replace: function(suggestion) { this.input.value = suggestion.value; }
        };
        if (inputKalkis) { new Awesomplete(inputKalkis, aweOptions); }
        if (inputVaris) { new Awesomplete(inputVaris, aweOptions); }
    });

//Siber Vatan

</script>



