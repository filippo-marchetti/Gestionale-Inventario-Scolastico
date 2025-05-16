<?php
session_start();

// Connessione al database
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

if (!isset($_GET['id'])) {
    die("ID aula non specificato.");
}

$idAula = $_GET['id'];

// Recupera descrizione aula
$stmtAula = $conn->prepare("SELECT descrizione FROM aula WHERE ID_Aula = ?");
$stmtAula->execute([$idAula]);
$descrizioneAula = $stmtAula->fetchColumn();

try {
    $stmt = $conn->prepare("
        SELECT i.codice_inventario, i.data_inventario, i.descrizione, s.nome AS nome_scuola
        FROM inventario i
        LEFT JOIN scuola s ON i.scuola_appartenenza = s.codice_meccanografico
        WHERE i.ID_Aula = ?
        ORDER BY i.data_inventario DESC
    ");
    $stmt->execute([$idAula]);
    $inventari = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Errore nel recupero degli inventari: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Inventari Aula <?= htmlspecialchars($descrizioneAula ?: $idAula) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../../assets/css/background.css">
    <link rel="stylesheet" href="../../assets/css/shared_style_user_admin.css">
    <link rel="stylesheet" href="../lista_dotazione/lista_dotazione.css">
    <link rel="stylesheet" href="inventari.css">
    <!-- Font Awesome per icone-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script>
    // Ricerca live per la tabella inventari
    window.addEventListener('DOMContentLoaded', () => {
        document.getElementById("filterInput").addEventListener("keyup", function () {
            const filterValue = this.value.toLowerCase();
            const rows = document.querySelectorAll(".lista-dotazioni table tbody tr");
            rows.forEach(row => {
                const codice = row.cells[0]?.textContent.toLowerCase() || "";
                const descrizione = row.cells[2]?.textContent.toLowerCase() || "";
                const match = codice.includes(filterValue) || descrizione.includes(filterValue);
                row.style.display = match ? "" : "none";
            });
        });
    });
    </script>
</head>
<body>
    <div class="container">
        <!-- sidebar -->
        <div class="sidebar">
            <div class="image"><img src="../../assets/images/logo_darzo.png" width="120px"></div>
            <div class="section-container">
                <br>
                <a href="../admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> AULE</span></div></a>
                <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="../magazzino/magazzino.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>
        </div>
        <!-- content -->
        <div class="content">
            <div class="logout">
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <h1>Inventari</h1>
            <div class="actions">
                <input type="text" id="filterInput" placeholder="Cerca per codice o descrizione" class="filter-input">
                <a href="../nuovo_inventario/nuovo_inventario.php?id=<?= urlencode($idAula) ?>" class="btn-add"><i class="fas fa-plus"></i> Nuovo Inventario</a>
            </div>
            <div class="lista-dotazioni">
                <table>
                    <thead>
                        <tr>
                            <th>Codice Inventario</th>
                            <th>Data Inventario</th>
                            <th>Descrizione</th>
                            <th>Scuola di appartenenza</th>
                            <th style="text-align:center;">Azioni</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($inventari) === 0): ?>
                            <tr>
                                <td colspan="5" class="no-results">Nessun inventario trovato per quest'aula.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($inventari as $inv): ?>
                                <tr>
                                    <td><?= htmlspecialchars($inv['codice_inventario']) ?></td>
                                    <td><?= htmlspecialchars($inv['data_inventario']) ?></td>
                                    <td><?= htmlspecialchars($inv['descrizione']) ?></td>
                                    <td><?= htmlspecialchars($inv['nome_scuola'] ?? 'Non specificata') ?></td>
                                    <td>
                                        <div class="div-action-btn">
                                            <a href="../dotazioni/dotazioni.php?codice=<?= urlencode($inv['codice_inventario']) ?>" title="Visualizza dotazioni">
                                                <button class="btn-action btn-green"><i class="fas fa-eye"></i></button>
                                            </a>
                                            <a href="modifica_inventario.php?codice=<?= urlencode($inv['codice_inventario']) ?>">
                                                <button class="btn-action btn-blu"><i class="fas fa-pen"></i></button>
                                            </a>
                                            <form method="POST" style="display:inline;">
                                                <button name="elimina" class="btn-action btn-red">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>