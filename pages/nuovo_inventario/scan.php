
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
            // Verifica se esiste
            $stmt = $conn->prepare("SELECT * FROM dotazione WHERE codice = ?");
            $stmt->execute([$codiceDotazione]);
            $dotazione = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($dotazione) {
                // Aggiunge ai codici spuntati per mostrarlo
                if (!in_array($codiceDotazione, $spuntati)) {
                    $spuntati[] = $codiceDotazione;
                }
            }else {
                $errore = "Codice dotazione non trovato.";
            }
        }

        // Redirect a nuovo_inventario.php
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
        <title>Scansione Dotazione - Aula <?php echo $idAula?></title>
        <link rel="stylesheet" href="../../assets/css/background.css">
        <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css">
        <link rel="stylesheet" href="../../../assets/css/shared_admin_subpages.css">
        <link rel="stylesheet" href="scan.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://unpkg.com/html5-qrcode"></script>
    </head>
    <body>
    <div class="container">
        <div class="sidebar">
            <div class="image"><img src="..\..\assets\images\placeholder.png" width="120px"></div>
            <!-- questa div conterrà i link delle schede -->
            <div class="section-container">
                <br>
                <?php
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <a href="../../user_page/aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    if($role == "admin"){
                        echo '<a href="..\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                    };
                ?>
                <a href="..\lista_dotazione\lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="..\dotazione_archiviata\dotazione_archiviata.php"><div class="section selected"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="..\dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>  
        </div>
        <div class="content">
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Bottone "indietro" -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Bottone logout -->
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
                    
            <h1>Scansione Dotazione<br><span class="subtitle">Aula <?php echo $idAula?></span></h1>

            <div class="scan-area">
                <form method="post" class="form-scan" autocomplete="off">
                    <label for="codice_dotazione"><i class="fa fa-barcode"></i> Inserisci o scansiona il codice dotazione:</label>
                    <input type="text" id="codice_dotazione" name="codice_dotazione" autofocus autocomplete="off" required>
                    <div id="reader" style="width:320px; height:240px; margin:0 auto 18px auto; display:none;"></div>
                    <button type="button" id="start-qr" class="btn-scan" style="margin-right:10px;"><i class="fa fa-qrcode"></i> Scansiona QR</button><br>
                    <button type="submit" class="btn-scan"><i class="fa fa-plus"></i> Aggiungi</button>
                </form>
                <div class="info" class="hint" style="margin-top: 10px;">
                    Puoi inserire manualmente il codice o usare la fotocamera per scansionare il QR code.
                </div>
                <?php if ($errore): ?>
                    <div class="error-code"><i class="fas fa-exclamation-circle"></i><?php echo $errore ?></div>
                <?php endif; ?>
            </div>
            
            <div class="actions-bar">
                <a href="nuovo_inventario.php?<?= http_build_query([
                    'id' => $idAula,
                    'codice_inventario' => $codiceInventario,
                    'spuntato' => $spuntati
                ]) ?>" class="btn-back"><i class="fa fa-arrow-left"></i> Torna all'inventario</a>
            </div>
        </div>
    </div>
        <script>
            let qrStarted = false;
            const reader = document.getElementById('reader');

            document.getElementById('start-qr').onclick = function () {
                if (qrStarted) return;
                qrStarted = true;

                reader.classList.add("active");

                const html5QrCode = new Html5Qrcode("reader");
                html5QrCode.start(
                    { facingMode: "environment" },
                    { fps: 10, qrbox: 250 },
                    qrCodeMessage => {
                        document.getElementById('codice_dotazione').value = qrCodeMessage;
                        html5QrCode.stop().then(() => {
                            qrStarted = false;
                            reader.classList.remove("active");
                            reader.innerHTML = "";
                        });
                    },
                    errorMessage => {
                        // Ignorato per evitare spam nella console
                    }
                ).catch(err => {
                    alert("Errore nell'accesso alla fotocamera: " + err);
                    qrStarted = false;
                    reader.classList.remove("active");
                });
            };
        </script>
    </body>
</html>
