<?php
    session_start(); 
    // Avvia la sessione per accedere alle variabili di sessione (es. username, ruolo)

    // Informazioni per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupero username e ruolo dalla sessione
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Controllo se l'utente è autenticato
    if(!is_null($username)){
        try {
            // Creo la connessione PDO al database MySQL
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            // Imposto la modalità di errore per PDO su eccezioni
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Se la connessione fallisce, blocco lo script e mostro il messaggio d'errore
            die("Connessione fallita: " . $e->getMessage());
        }

        // Se viene premuto il bottone "archivia" in POST
        if(isset($_POST["archivia"])){
            // Preparo la query per aggiornare lo stato della dotazione a "archiviato" e rimuovere l'aula associata
            $stmt = $conn->prepare("UPDATE dotazione SET ID_aula = NULL, stato = 'archiviato' WHERE codice = :codice");
            // Associo il parametro codice al valore inviato nel POST
            $stmt->bindParam(':codice', $_POST['archivia']);
            // Eseguo la query
            $stmt->execute();
        }

        // Se viene premuto il bottone "scarta" in POST
        if(isset($_POST["scarta"])){
            // Preparo la query per aggiornare lo stato della dotazione a "scartato" e rimuovere l'aula associata
            $stmt = $conn->prepare("UPDATE dotazione SET ID_aula = NULL, stato = 'scartato' WHERE codice = :codice");
            // Associo il parametro codice al valore inviato nel POST
            $stmt->bindParam(':codice', $_POST['scarta']);
            // Eseguo la query
            $stmt->execute();
        }
    } else {
        // Se l'utente non è autenticato, lo reindirizzo alla pagina di logout (es. per sicurezza)
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- Collegamento ai file CSS esterni per lo stile -->
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">
        <link rel="stylesheet" href="lista_dotazione.css">
        <title>Document</title>
        <!-- Font Awesome per icone -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="lista_dotazione.js"></script> <!-- Script per funzionalità lato client -->
    </head>
    <body>
        <div class="container">
            <!-- Sidebar laterale con menu di navigazione -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <div class="section-container">
                    <br>
                    <?php
                        // Se l'utente è admin, mostro link alla pagina admin, altrimenti a pagina user
                        if($role == 'admin') {
                            echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        } else {
                            echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                        }
                    ?>
                    <!-- Altri link fissi nel menu -->
                    <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <?php
                        // Solo admin vede queste sezioni extra
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
            <!-- Contenuto principale della pagina -->
            <div class="content">
                <!-- Barra superiore con logout e pulsante "indietro" -->
                <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <!-- Bottone per tornare alla pagina precedente -->
                    <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                    <!-- Bottone logout -->
                    <a class="logout-btn" href="../../logout/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <h1>Dotazioni</h1>
                <div class="actions">
                    <!-- Input per filtro/search lato client -->
                    <input type="text" id="filterInput" placeholder="Cerca per nome o codice" class="filter-input">
                </div>

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
                                // Query per prendere tutte le dotazioni che hanno un'aula associata e sono nello stato "presente"
                                $stmt = $conn->query("SELECT * FROM dotazione WHERE ID_aula IS NOT NULL AND stato LIKE 'presente'");
                                $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                // Ciclo ogni dotazione recuperata per mostrarla nella tabella
                                foreach ($dotazioni as $dotazione) {  
                                    echo "<tr>";
                                        echo "<td>".$dotazione['codice']."</td>"; // Codice univoco
                                        echo "<td>".$dotazione['nome']."</td>"; // Nome dotazione
                                        echo "<td>".$dotazione['categoria']."</td>"; // Categoria
                                        echo "<td>".$dotazione['descrizione']."</td>"; // Descrizione
                                        echo "<td>".$dotazione['prezzo_stimato']."€</td>"; // Prezzo stimato con simbolo €
                                        echo "<td>".$dotazione['ID_aula']."</td>"; // Aula associata
                                        ?>
                                            <td>
                                                <div class="div-action-btn">
                                                    <!-- Link alla pagina per modificare la dotazione, passa il codice dotazione -->
                                                    <a href="modifica_dotazione/modifica_dotazione.php?codice=<?php echo $dotazione['codice']?>&start=attiva">
                                                        <button name="modifica" class="btn-action btn-green" value="">
                                                            <i class="fas fa-pen"></i>
                                                        </button>
                                                    </a>
                                                    <!-- Link che apre il PDF con QR code della dotazione in una nuova finestra -->
                                                    <a href="..\generazione_QR\genera_pdf.php?id=<?php echo $dotazione['codice']; ?>" target="_blank">
                                                        <button name="qrcode" class="btn-action btn-blu">
                                                            <i class="fas fa-qrcode"></i>
                                                        </button>
                                                    </a>
                                                    <!-- Form con bottone per archiviare la dotazione -->
                                                    <form method="POST">
                                                        <button name="archivia" value="<?php echo $dotazione['codice']?>" class="btn-action btn-yellow">
                                                            <i class="fas fa-warehouse"></i>
                                                        </button>
                                                    </form>
                                                    <!-- Form con bottone per scartare la dotazione -->
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
