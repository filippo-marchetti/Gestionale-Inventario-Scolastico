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

if (!isset($_GET['id'])) {
    die("ID aula non specificato.");
}

$idAula = $_GET['id'];

// Recupera il codice dell'ultimo inventario per quell'aula
$stmt = $conn->prepare("
    SELECT codice_inventario 
    FROM inventario 
    WHERE ID_Aula = ? 
    ORDER BY data_inventario DESC 
    LIMIT 1
");
$stmt->execute([$idAula]);
$lastInventario = $stmt->fetch(PDO::FETCH_ASSOC);

$dotazioni = [];

if ($lastInventario) {
    $codiceInventario = $lastInventario['codice_inventario'];

    // Recupera le dotazioni relative a quell'inventario
    $stmt = $conn->prepare("
        SELECT d.codice, d.nome, d.categoria, d.descrizione, d.stato, d.prezzo_stimato
        FROM riga_inventario ri
        JOIN dotazione d ON ri.codice_dotazione = d.codice
        WHERE ri.codice_inventario = ?
    ");
    $stmt->execute([$codiceInventario]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Nuovo Inventario - Aula <?= htmlspecialchars($idAula) ?></title>
</head>
<body>
<div class="container">
    <h1>Nuovo Inventario - Aula <?= htmlspecialchars($idAula) ?></h1>

    <?php if (count($dotazioni) === 0): ?>
        <p>Nessuna dotazione trovata per l'ultimo inventario.</p>
    <?php else: ?>
        <form action="salva_inventario.php" method="post">
            <input type="hidden" name="id_aula" value="<?= htmlspecialchars($idAula) ?>">
            <table>
                <thead>
                    <tr>
                        <th>Presente</th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Descrizione</th>
                        <th>Stato</th>
                        <th>Prezzo Stimato</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dotazioni as $dot): ?>
                        <tr>
                            <td><input type="checkbox" name="presenti[]" value="<?= htmlspecialchars($dot['codice']) ?>" checked></td>
                            <td><?= htmlspecialchars($dot['nome']) ?></td>
                            <td><?= htmlspecialchars($dot['categoria']) ?></td>
                            <td><?= htmlspecialchars($dot['descrizione']) ?></td>
                            <td><?= htmlspecialchars($dot['stato']) ?></td>
                            <td><?= htmlspecialchars($dot['prezzo_stimato']) ?> €</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <br>
            <label>Descrizione nuovo inventario:</label><br>
            <textarea name="descrizione" rows="4" cols="50" required></textarea>
            <br><br>
            <button type="submit">Salva Inventario</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
