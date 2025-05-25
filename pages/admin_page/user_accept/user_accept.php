<?php
    session_start();

    // Informazioni per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera username e ruolo dalla sessione
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    $pk_utente = ""; // Variabile per memorizzare l'username da accettare/rifiutare
    $scelta = "";    // Variabile per indicare lo stato da impostare

    // Controlla che l'utente sia loggato e sia admin
    if(!is_null($username) && $role == "admin"){
        try {
            // Connessione al database con PDO e gestione eccezioni
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Se la connessione fallisce, mostra il messaggio di errore e termina
            die("Connessione fallita: " . $e->getMessage());
        }

        // Se è stato premuto il bottone per accettare un singolo utente
        if (isset($_POST['accetta'])) {
            $pk_utente = $_POST['accetta']; // Username utente da accettare
            $scelta = "attivo";             // Stato da assegnare

            // Aggiorna lo stato dell'utente a 'attivo' nel database
            $stmt = $conn->prepare("UPDATE utente SET stato = :stato WHERE username = :username");
            $stmt->bindParam(':stato', $scelta);
            $stmt->bindParam(':username', $pk_utente);
            $stmt->execute();

            // Ricarica la pagina per aggiornare la lista
            header("Location: " . $_SERVER['PHP_SELF']);
        }
        // Se è stato premuto il bottone per rifiutare un singolo utente
        else if (isset($_POST['rifiuta'])) {
            $pk_utente = $_POST['rifiuta']; // Username utente da rifiutare

            // Elimina l'utente dal database
            $stmt = $conn->prepare("DELETE FROM utente WHERE username = :username");
            $stmt->bindParam(':username', $pk_utente);
            $stmt->execute();

            // Ricarica la pagina per aggiornare la lista
            header("Location: " . $_SERVER['PHP_SELF']);
        }

        // Se è stato premuto il bottone per accettare tutti gli utenti in attesa
        if (isset($_POST['accept-all'])) {
            $scelta = "attivo"; // Stato da assegnare
            // Aggiorna tutti gli utenti con stato 'attesa' a 'attivo'
            $stmt = $conn->prepare("UPDATE utente SET stato = :stato WHERE stato = 'attesa'");
            $stmt->bindParam(':stato', $scelta);
            $stmt->execute();

            // Ricarica la pagina
            header("Location: " . $_SERVER['PHP_SELF']);
        }
        // Se è stato premuto il bottone per rifiutare tutti gli utenti in attesa
        else if (isset($_POST['reject-all'])) {
            // Elimina tutti gli utenti con stato 'attesa'
            $stmt = $conn->prepare("DELETE FROM utente WHERE stato = 'attesa'");
            $stmt->execute();

            // Ricarica la pagina
            header("Location: " . $_SERVER['PHP_SELF']);
        }

    } else {
        // Se l'utente non è loggato o non è admin, effettua il logout
        header("Location: ..\..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Link ai file CSS per stile -->
        <link rel="stylesheet" href="..\..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="user_accept.css">
        <title>Accetta Utenti</title>
        <!-- Font Awesome per icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <!-- Sidebar laterale con link di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\..\assets\images\placeholder.png" width="120px"></div>
                <div class="section-container">
                    <br>
                    <?php
                        // Link home differenziati per ruolo
                        if($role == 'admin') {
                            echo '<a href="../admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <!-- Link principali del menu -->
                    <a href="../../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        // Se admin, mostra altre opzioni di menu
                        if($role == "admin"){
                            echo '<a href="..\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <a href="../../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>  
                </div>  
            </div>

            <!-- Content principale della pagina -->
            <div class="content">
                <!-- Barra logout e back -->
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

                <h1>Richieste Account</h1>

                <!-- Pulsanti per accettare o rifiutare tutte le richieste -->
                <form class="btn-container" method="post">
                    <button class="btn btn-blu" name="accept-all"><i class="fas fa-check"></i>ACCETTA TUTTI</button>
                    <button class="btn btn-red" name="reject-all"><i class="fas fa-times"></i>RIFIUTA TUTTI</button>
                </form>

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
                                // Recupera tutti gli utenti in stato 'attesa' (richieste in attesa di approvazione)
                                $stmt = $conn->query("SELECT * FROM utente WHERE stato = 'attesa'");
                                $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Cicla ogni utente e crea una riga tabella con dati e pulsanti azione
                                foreach ($utenti as $utente) {  
                                    echo "<tr>";
                                        echo "<td>".$utente['username']."</td>";
                                        echo "<td>".$utente['nome']."</td>";
                                        echo "<td>".$utente['cognome']."</td>";
                                        echo "<td>".$utente['email']."</td>";
                                        echo "<td>".$utente['scuola_appartenenza']."</td>";
                                        ?>
                                            <td style="text-align: center;">
                                                <form method="POST">
                                                    <!-- Pulsante per accettare il singolo utente -->
                                                    <button type="submit" name="accetta" value="<?php echo $utente['username']?>" class="btn-action btn-blu">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <!-- Pulsante per rifiutare il singolo utente -->
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
