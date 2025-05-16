<?php
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Errore di connessione: " . $e->getMessage());
}

$idAula = $_GET['id'] ?? null;
$codiceInventario = $_GET['codice_inventario'] ?? null;
$spuntati = $_GET['spuntato'] ?? [];
if (!is_array($spuntati)) {
    $spuntati = [$spuntati];
}

$messaggio = "";

if (!$idAula || !$codiceInventario) {
    die("Dati mancanti.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $codiceDotazione = $_POST['codice_dotazione'] ?? '';

    if ($codiceDotazione) {
        $stmt = $conn->prepare("SELECT 1 FROM riga_inventario WHERE codice_inventario = ? AND codice_dotazione = ?");
        $stmt->execute([$codiceInventario, $codiceDotazione]);
        $giaPresente = $stmt->fetchColumn();

        if ($giaPresente) {
            $spuntati[] = $codiceDotazione;
        } else {
            $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = ?");
            $stmt->execute([$codiceDotazione]);
            $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dotazione) {
                $stmt = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
                $stmt->execute([$codiceDotazione, $codiceInventario]);

                $stmt = $conn->prepare("UPDATE dotazione SET ID_Aula = ? WHERE codice = ?");
                $stmt->execute([$idAula, $codiceDotazione]);

                $spuntati[] = $codiceDotazione;
            } else {
                $messaggio = "❌ Codice dotazione non trovato.";
            }
        }

        if (empty($messaggio)) {
            $query = http_build_query([
                'id' => $idAula,
                'codice_inventario' => $codiceInventario,
                'spuntato' => $spuntati
            ]);
            header("Location: scan.php?$query");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scansione Dotazione - Aula <?= htmlspecialchars($idAula) ?></title>
    <link rel="stylesheet" href="../../assets/css/background.css">
    <link rel="stylesheet" href="../assets/css/shared_style_user_admin.css">
    <link rel="stylesheet" href="scan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode"></script>
</head>
<body>
<div class="container">
    <h1>Scansione Dotazione<br><span class="subtitle">Aula <?= htmlspecialchars($idAula) ?></span></h1>

    <div class="scan-area">
        <form method="post" class="form-scan" autocomplete="off">
            <label for="codice_dotazione"><i class="fa fa-barcode"></i> Inserisci o scansiona il codice dotazione:</label>
            <input type="text" id="codice_dotazione" name="codice_dotazione" autofocus autocomplete="off" required>
            <div id="reader" style="width:320px; margin:0 auto 18px auto; display:none;"></div>
            <button type="button" id="start-qr" class="btn-scan" style="margin-right:10px;"><i class="fa fa-qrcode"></i> Scansiona QR</button>
            <button type="submit" class="btn-scan"><i class="fa fa-plus"></i> Aggiungi</button>
        </form>
        <div class="info" style="margin-top:10px; color:#1976d2;">
            Puoi inserire manualmente il codice o usare la fotocamera per scansionare il QR code.
        </div>
        <?php if ($messaggio): ?>
            <div class="error"><?= htmlspecialchars($messaggio) ?></div>
        <?php endif; ?>
    </div>

    <?php if (!empty($spuntati)): ?>
        <div class="dotazioni-list">
            <h3>Dotazioni scansionate:</h3>
            <ul>
                <?php foreach ($spuntati as $codice): ?>
                    <li><i class="fa fa-check-circle" style="color:green"></i> <?= htmlspecialchars($codice) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="actions-bar">
        <a href="nuovo_inventario.php?<?= http_build_query([
            'id' => $idAula,
            'codice_inventario' => $codiceInventario,
            'spuntato' => $spuntati
        ]) ?>" class="btn-back"><i class="fa fa-arrow-left"></i> Torna al nuovo inventario</a>
    </div>
</div>
<script>
let qrStarted = false;
document.getElementById('start-qr').onclick = function() {
    if (qrStarted) return;
    qrStarted = true;
    document.getElementById('reader').style.display = "block";
    const html5QrCode = new Html5Qrcode("reader");
    html5QrCode.start(
        { facingMode: "environment" },
        { fps: 10, qrbox: 250 },
        qrCodeMessage => {
            document.getElementById('codice_dotazione').value = qrCodeMessage;
            html5QrCode.stop();
            qrStarted = false;
            document.getElementById('reader').innerHTML = "";
            document.getElementById('reader').style.display = "none";
        },
        errorMessage => {}
    ).catch(err => {
        alert("Errore nell'accesso alla fotocamera: " + err);
        qrStarted = false;
        document.getElementById('reader').style.display = "none";
    });
};
</script>
</body>
</html>