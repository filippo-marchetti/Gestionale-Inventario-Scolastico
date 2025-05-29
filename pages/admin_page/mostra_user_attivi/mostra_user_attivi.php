<?php
    session_start();

    // Configurazione dati di connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupero username e ruolo dalla sessione
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Verifica che l'utente sia autenticato e abbia ruolo admin
    if(!is_null($username) && $role == "admin"){
        try {
            // Connessione al database tramite PDO con gestione eccezioni
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In caso di errore di connessione, termina lo script con messaggio
            die("Connessione fallita: " . $e->getMessage());
        }
    }else{
        // Se utente non autenticato o non admin, reindirizza alla pagina di logout
        header("Location: ..\..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <!-- Meta e stili per la pagina -->
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Collegamenti ai fogli di stile comuni e specifici -->
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="mostra_user_attivi.css">
        <title>Utenti attivi</title>
        <!-- Font Awesome per icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <!-- Sidebar di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- Contenitore link di navigazione -->
                <div class="section-container">
                    <br>
                    <?php
                        // Link Home differenziati a seconda del ruolo (admin o altro)
                        if($role == 'admin') {
                            echo '<a href="../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        // Se utente è admin, mostra link aggiuntivi specifici per amministrazione
                        if($role == "admin"){
                            echo '<a href="..\mostra_user_attivi\mostra_user_attivi.php"><div class="section selected"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <!-- Altri link comuni -->
                    <a href="../../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>  
                </div>  
            </div>
            <!-- Area principale contenuti -->
            <div class="content">
                <!-- Barra logout e navigazione indietro -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone "indietro" -->
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <!-- Titolo pagina -->
                <h1>Utenti attivi</h1>

                <!-- Sezione contenente la tabella degli utenti attivi -->
                <div class="user-request">
                    <table>
                        <thead>
                            <td>Username</td>
                            <td>Nome</td>
                            <td>Cognome</td>
                            <td>Email</td>
                            <td>Scuola</td>
                            <td style="text-align: center;">Azioni</td>
                        </thead>
                        <tbody>
                            <?php
                                // Esecuzione query per ottenere gli utenti con stato 'attivo'
                                $stmt = $conn->query("SELECT * FROM utente WHERE stato = 'attivo'");
                                $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Ciclo di stampa righe tabella con dati utenti
                                foreach ($utenti as $utente) {  
                                    echo "<tr>";
                                        echo "<td>".$utente['username']."</td>";
                                        echo "<td>".$utente['nome']."</td>";
                                        echo "<td>".$utente['cognome']."</td>";
                                        echo "<td>".$utente['email']."</td>";
                                        echo "<td>".$utente['scuola_appartenenza']."</td>";
                            ?>
                                        <!-- Colonna con pulsanti azioni (accetta / rifiuta) -->
                                        <td style="text-align: center;">
                                            <form method="POST">
                                                <!-- Pulsante per rifiutare l'utente (valore è username) -->
                                                <button type="submit" name="rifiuta" value="<?php echo $utente['username']?>" class="btn-action btn-red">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </td>
                            <?php
                                    echo "</tr>";
                                }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </body>
</html>
