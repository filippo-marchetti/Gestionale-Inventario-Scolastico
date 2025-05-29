<?php
    session_start();

    $role = $_SESSION['role'] ?? null;

    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $errore = "";

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

    if (!$idAula || !$codiceInventario) {
        die("Parametri mancanti.");
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $codiceDotazione = trim($_POST['codice_dotazione'] ?? '');

        if ($codiceDotazione) {
            $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = ?");
            $stmt->execute([$codiceDotazione]);
            $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dotazione) {
                if (!in_array($codiceDotazione, $spuntati)) {
                    $spuntati[] = $codiceDotazione;
                }
            } else {
                $errore = "Codice dotazione non trovato.";
            }
        }

        if (empty($errore)) {
            $query = http_build_query([
                'id' => $idAula,
                'codice_inventario' => $codiceInventario,
                'spuntato' => $spuntati
            ]);
            header("Location: nuovo_inventario.php?$query");
            exit;
        }
    }
    ?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scansione Dotazione - Aula <?php echo $idAula ?></title>
    <link rel="stylesheet" href="../../assets/css/background.css">
    <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css">
    <link rel="stylesheet" href="../../../assets/css/shared_admin_subpages.css">
    <link rel="stylesheet" href="scan.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <style>
        
    </style>
</head>
<body>
<div class="container">
    <div class="sidebar">
        <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
        <div class="section-container">
            <br>
            <?php
            if ($role == 'admin') {
                echo '<a href="../../pages/admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
            } else {
                echo '<a href="../user_page/user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
            }
            ?>
            <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
            <?php
            if ($role == "admin") {
                echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
            };
            ?>
            <a href="..\lista_dotazione\lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
            <a href="..\dotazione_archiviata\dotazione_archiviata.php"><div class="section selected"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
            <a href="..\dotazione_eliminata\dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
            <a href="..\dotazione_mancante\dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
            <a href="..\impostazioni\impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
        </div>
    </div>

    <div class="content">
        <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                <i class="fas fa-chevron-left"></i>
            </a>
            <a class="logout-btn" href="../../logout/logout.php">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>

        <h1>Scansione Dotazione<br><span class="subtitle">Aula <?php echo $idAula ?></span></h1>

        <div class="scan-area">
            <form method="post" class="form-scan" autocomplete="off">
                <label for="codice_dotazione"><i class="fa fa-barcode"></i> Inserisci o scansiona il codice dotazione:</label>
                <input type="text" id="codice_dotazione" name="codice_dotazione" autofocus autocomplete="off" required>
                <button type="button" id="start-qr" class="btn-scan" style="margin-right:10px;"><i class="fa fa-qrcode"></i> Scansiona QR</button><br>
                <button type="submit" class="btn-scan"><i class="fa fa-plus"></i> Aggiungi</button>
            </form>

            <div class="info" style="margin-top: 10px;">
                Puoi inserire manualmente il codice o usare la fotocamera per scansionare il QR code.
            </div>

            <?php if ($errore): ?>
                <div class="error-code"><i class="fas fa-exclamation-circle"></i><?php echo $errore ?></div>
            <?php endif; ?>
        </div>

        <div class="actions-bar">
            <form method="get" action="javascript:history.back();" style="display:inline;">
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($idAula); ?>">
                <input type="hidden" name="codice_inventario" value="<?php echo htmlspecialchars($codiceInventario); ?>">
                <?php foreach ($spuntati as $val): ?>
                    <input type="hidden" name="spuntato[]" value="<?php echo htmlspecialchars($val); ?>">
                <?php endforeach; ?>
                <button type="submit" class="btn-back"><i class="fa fa-arrow-left"></i> Torna all'inventario</button>
            </form>
        </div>
    </div>
</div>

<div id="reader-overlay">
    <button id="close-scanner">Chiudi</button>
    <div id="reader"></div>
</div>

<script>
    const startButton = document.getElementById("start-qr");
    const overlay = document.getElementById("reader-overlay");
    const closeButton = document.getElementById("close-scanner");
    const input = document.getElementById("codice_dotazione");

    let qrScanner = null;

    startButton.addEventListener("click", function () {
        overlay.style.display = "flex";

        if (!qrScanner) {
            qrScanner = new Html5Qrcode("reader");
        }

        qrScanner.start(
            { facingMode: "environment" },
            { fps: 10, qrbox: 250 },
            function (decodedText) {
                input.value = decodedText;
                stopScanner();
            },
            function (errorMessage) {
                // Nessuna azione per errori continui di scansione
            }
        ).catch(err => {
            alert("Errore nell'avvio dello scanner: " + err);
            overlay.style.display = "none";
        });
    });

    function stopScanner() {
        if (qrScanner && qrScanner.getState() === Html5QrcodeScannerState.SCANNING) {
            qrScanner.stop().then(() => {
                document.getElementById("reader").innerHTML = "";
                overlay.style.display = "none";
            }).catch(err => {
                console.error("Errore nella chiusura dello scanner:", err);
                overlay.style.display = "none";
            });
        } else {
            overlay.style.display = "none";
        }
    }

    closeButton.addEventListener("click", stopScanner);
</script>

</body>
</html>
