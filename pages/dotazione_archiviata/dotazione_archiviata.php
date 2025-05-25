<?php
    session_start(); // Avvia la sessione per poter usare variabili di sessione

    // Info per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera username e ruolo dallla sessione
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Controlla se l'utente è loggato
    if(!is_null($username)){
        try {
            // Connessione al database usando PDO
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Se la connessione fallisce, stampa l'errore ed esce
            die("Connessione fallita: " . $e->getMessage());
        }

        // Se è stato premuto il pulsante "scarta" per archiviare/scartare una dotazione
        if(isset($_POST["scarta"])){
            // Aggiorna la dotazione: toglie l'aula e imposta stato a 'scartato'
            $stmt = $conn->prepare("UPDATE dotazione SET ID_aula = NULL, stato = 'scartato' WHERE codice = :codice");
            $stmt->bindParam(':codice', $_POST['scarta']); // Codice dotazione passato col pulsante
            $stmt->execute(); // Esegue l'update
        }
    }else{
        // Se l'utente non è loggato, reindirizza al logout (che presumibilmente reindirizza alla pagina di login)
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Collegamenti ai fogli di stile -->
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">
        <link rel="stylesheet" href="lista_dotazione.css">
        <title>Magazzino</title>
        <!-- Font Awesome per le icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="..\lista_dotazione\lista_dotazione.js"></script> <!-- Script JS per eventuali funzioni lato client -->
    </head>
    <body>
        <div class="container">
            <!-- Sidebar laterale con menu di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\placeholder.png" width="120px"></div>
                <div class="section-container">
                    <br>
                    <?php
                        // Se admin, mostra link home admin, altrimenti home user
                        if($role == 'admin') {
                            echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        // Se admin, mostra link per gestione tecnici e utenti
                        if($role == "admin"){
                            echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                            echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                            echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                        };
                    ?>
                    <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                    <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>

            <!-- Area principale contenuto -->
            <div class="content">
                <!-- Barra logout con bottone indietro e logout -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone "indietro" usa javascript per tornare alla pagina precedente -->
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout per uscire -->
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>Dotazioni archiviate</h1>

                <!-- Azioni: barra di ricerca e pulsante aggiungi -->
                <div class="actions">
                    <input type="text" id="filterInput" placeholder="Cerca per nome o codice" class="filter-input">
                    <form method="post" action="aggiungi_dotazione_archiviata\aggiungi_dotazione_archiviata.php">
                        <button class="btn-add"><i class="fas fa-plus"></i>Aggiungi</button>
                    </form>
                </div>

                <!-- Tabella con la lista delle dotazioni archiviate -->
                <div class="lista-dotazioni">
                    <table>
                        <thead>
                            <td>Codice</td>
                            <td>Nome</td>
                            <td>Categoria</td>
                            <td>Descrizione</td>
                            <td>Prezzo Stimato</td>
                            <td>Aula</td>
                            <td style="text-align: center;">Azioni</td>
                        </thead>
                        <tbody>
                            <?php
                                // Recupera tutte le dotazioni archiviate (senza aula e stato 'archiviato')
                                $stmt = $conn->query("SELECT * FROM dotazione WHERE ID_aula IS NULL AND stato LIKE 'archiviato'");
                                $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Cicla tutte le dotazioni e le mostra in tabella
                                foreach ($dotazioni as $dotazione) {  
                                    echo "<tr>";
                                        echo "<td>".$dotazione['codice']."</td>";
                                        echo "<td>".$dotazione['nome']."</td>";
                                        echo "<td>".$dotazione['categoria']."</td>";
                                        echo "<td>".$dotazione['descrizione']."</td>";
                                        echo "<td>".$dotazione['prezzo_stimato']."€</td>";
                                        echo "<td>".$dotazione['ID_aula']."</td>"; // Sarà sempre NULL in questo caso
                                        ?>
                                            <td>
                                                <div class="div-action-btn">
                                                    <!-- Pulsante per modificare la dotazione: rimanda a pagina modifica passando il codice -->
                                                    <a href="..\lista_dotazione\modifica_dotazione\modifica_dotazione.php?codice=<?php echo $dotazione['codice']?>&start=archivio">
                                                        <button name="modifica" class="btn-action btn-green" value="">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                    </a>

                                                    <!-- Pulsante per aprire PDF con QR code relativo alla dotazione -->
                                                    <a href="..\generazione_QR\genera_pdf.php?id=<?php echo $dotazione['codice']; ?>" target="_blank">
                                                        <button name="qrcode" class="btn-action btn-blu">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </a>

                                                    <!-- Pulsante per scartare la dotazione: invia form con POST per aggiornare stato -->
                                                    <form method="POST">
                                                        <button name="scarta" value="<?php echo $dotazione['codice']?>" class="btn-action btn-red">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
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
