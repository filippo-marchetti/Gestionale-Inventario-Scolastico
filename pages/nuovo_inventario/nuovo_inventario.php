<?php
    session_start();

    $role = $_SESSION['role'];

    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    $idAula = $_GET['id'] ?? null;
    $codiciDaSpuntare = $_GET['spuntato'] ?? [];
    if (!is_array($codiciDaSpuntare)) {
        $codiciDaSpuntare = [$codiciDaSpuntare];
    }

    if (!$idAula) {
        die("ID aula non specificato.");
    }

    // Generazione codice inventario
    $iniziale = substr($idAula, 0, 1);
    $finale = substr($idAula, -1, 1); //offset -1 restituisce l'ultima posizione
    $data = date('dmy');
    $codiceInventario = strtoupper($iniziale . $finale . $data);

    // Assicura codice univoco
    $stmt = $conn->prepare("SELECT 1 FROM inventario WHERE codice_inventario = ?");
    while (true) {
        $stmt->execute([$codiceInventario]);
        if (!$stmt->fetchColumn()) break;
        $codiceInventario = strtoupper($iniziale . $finale . str_pad(strval(random_int(0, 999999)), 7, '0', STR_PAD_LEFT));
        $codiceInventario = substr($codiceInventario, 0, 9);
    }

    // Recupera ultimo inventario in ordine cronologico
    $stmt = $conn->prepare("
        SELECT codice_inventario
        FROM inventario
        WHERE ID_aula = ?
        ORDER BY data_inventario DESC
        LIMIT 1
    ");

    $stmt->execute([$idAula]);
    $lastInv = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Dotazioni: da ultimo inventario + da scan.php
    $dotazioni = [];
    $codiciPresenti = [];

    if ($lastInv) {
        $stmt = $conn->prepare("
            SELECT d.codice, d.nome, d.categoria, d.stato, d.ID_aula AS aula_corrente
            FROM riga_inventario ri
            JOIN dotazione d ON ri.codice_dotazione = d.codice
            WHERE ri.codice_inventario = ?
        ");
        $stmt->execute([$lastInv['codice_inventario']]);
        $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $codiciPresenti = array_column($dotazioni, 'codice');
    }

    // Aggiungi dotazioni da scan.php se non già presenti
    $nuoviCodici = array_diff($codiciDaSpuntare, $codiciPresenti);
    if (!empty($nuoviCodici)) {
        $placeholders = implode(',', array_fill(0, count($nuoviCodici), '?'));
        $stmt = $conn->prepare("
            SELECT codice, nome, categoria, stato, ID_aula AS aula_corrente
            FROM dotazione
            WHERE codice IN ($placeholders)
        ");
        $stmt->execute($nuoviCodici);
        $nuoveDotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dotazioni = array_merge($dotazioni, $nuoveDotazioni);
    }

    $errors = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $descrizione = trim($_POST['descrizione'] ?? '');
        $spuntati = $_POST['dotazione_presente'] ?? [];

    if (!$descrizione) {
        $errors[] = "Inserire una descrizione.";
    }

    if (empty($errors)) {
        // Inserisci inventario
        $stmt = $conn->prepare("
            INSERT INTO inventario (codice_inventario, data_inventario, descrizione, ID_aula, scuola_appartenenza, ID_tecnico)
            VALUES (?, NOW(), ?, ?, NULL, '')
        ");
        $stmt->execute([$codiceInventario, $descrizione, $idAula]);

        // Inserisci righe + aggiorna dotazioni
        $stmtAdd = $conn->prepare("INSERT INTO riga_inventario (codice_dotazione, codice_inventario) VALUES (?, ?)");
        $stmtUpdate = $conn->prepare("UPDATE dotazione SET ID_aula = ?, stato = 'presente' WHERE codice = ?");

        foreach ($spuntati as $codice) {
            $stmtAdd->execute([$codice, $codiceInventario]);
            $stmtUpdate->execute([$idAula, $codice]);
        }

        $_SESSION['success_message'] = "Inventario creato e stato dotazioni aggiornato a 'presente'.";
        header("Location: inventari.php?id=" . urlencode($idAula));
        exit;
    }
    }
?>

<!DOCTYPE html>
<html lang="it">
    <head>
        <meta charset="UTF-8">
        <title>Nuovo Inventario</title>
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="nuovo_inventario.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <?php
                        if($role == 'admin') {
                            echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        if($role == "admin"){
                            echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>      
                </div>  
            </div>
            <div class="content">
                <h1>Nuovo Inventario - Aula <?php echo $idAula ?></h1>

                <a href="scan.php?<?= http_build_query([
                    'id' => $idAula,
                    'codice_inventario' => $codiceInventario,
                    'spuntato' => $codiciDaSpuntare
                ]) ?>" class="btn-scan">Scansiona Dotazione</a>

                <?php if (!empty($errors)): ?>
                    <div class="error">
                        <ul>
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo $err ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="codice_inventario" value="<?php echo $codiceInventario ?>">

                    <label>Codice Inventario:</label>
                    <input type="text" value="<?php echo $codiceInventario ?>" readonly><br><br>

                    <label>Descrizione:</label>
                    <input type="text" name="descrizione" required style="width:100%;"><br><br>

                    <h3>Dotazioni incluse:</h3>
                    <?php foreach ($dotazioni as $d): ?>
                        <?php
                            $codice = $d['codice'];
                            $altAula = ($d['aula_corrente'] && $d['aula_corrente'] !== $idAula);
                            $aggiuntaDaScan = in_array($codice, $codiciDaSpuntare);
                        ?>
                        <div class="dotazione">
                            <label>
                                <input type="checkbox" name="dotazione_presente[]" value="<?php echo $codice ?>"
                                    <?= in_array($codice, $codiciDaSpuntare) ? 'checked' : '' ?>>
                                <strong><?php echo $d['nome'] ?></strong> (<?php echo $d['categoria']?>)
                            </label><br>
                            Codice: <?php echo $codice ?> | Stato: <?php echo $d['stato'] ?>
                            <?php if ($altAula): ?>
                                <div class="warning">⚠ Attualmente si trova in aula <?php echo $d['aula_corrente'] ?></div>
                            <?php endif; ?>
                            <?php if ($aggiuntaDaScan): ?>
                                <div class="success-scan">Aggiunta tramite scansione</div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>

                    <br><button type="submit" class="btn">Salva Inventario</button>
                </form>
            </div>
        </div>
    </body>
</html>
