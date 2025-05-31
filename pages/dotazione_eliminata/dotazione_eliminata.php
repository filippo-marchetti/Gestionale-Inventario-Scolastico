<?php
    session_start(); // Avvia la sessione per gestire variabili utente

    // Informazioni per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera i dati di sessione: username e ruolo dell'utente
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Verifica che l'utente sia autenticato (username non nullo)
    if(!is_null($username)){
        try {
            // Crea la connessione PDO al database
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            // Imposta la modalità di errore PDO su eccezioni
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // Se la connessione fallisce, termina con messaggio di errore
            die("Connessione fallita: " . $e->getMessage());
        }
        
        // Se il form ha inviato il campo "abilita" (pulsante riabilita dotazione premuto)
        if(isset($_POST["abilita"])){
            // Prepara la query per aggiornare la dotazione indicata
            // Imposta ID_aula a NULL, stato a 'archiviato' e prezzo_stimato a 0
            $stmt = $conn->prepare("UPDATE dotazione SET ID_aula = 'magazzino', stato = 'archiviato' WHERE codice = :codice");
            // Associa il parametro :codice al valore inviato nel POST
            $stmt->bindParam(':codice', $_POST['abilita']);
            // Esegue la query di aggiornamento
            $stmt->execute();
        }
    } else {
        // Se non è loggato, reindirizza al logout (o pagina login)
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Setta la codifica dei caratteri -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Responsive design -->
    
    <!-- Collegamenti ai fogli di stile CSS -->
    <link rel="stylesheet" href="..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">
    <link rel="stylesheet" href="lista_dotazione.css">
    
    <title>Dotazione Eliminata</title> <!-- Titolo della pagina -->
    
    <!-- Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Script JavaScript per funzionalità lista dotazione -->
    <script src="..\lista_dotazione\lista_dotazione.js"></script>
</head>
<body>
    <div class="container"> <!-- Contenitore principale -->
        <div class="sidebar"> <!-- Sidebar di navigazione -->
            <div class="image">
                <img src="..\..\assets\images\logo_darzo.png" width="120px"> <!-- Immagine placeholder -->
            </div>
            <div class="section-container"> <!-- Contenitore link sezioni -->
                <br>
                <?php
                    // Se ruolo admin, link HOME ad area admin
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        // Altrimenti link HOME utente normale
                        echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <!-- Link fissi -->
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                <?php
                    // Solo admin può vedere link aggiuntivi
                    if($role == "admin"){
                        echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                    };
                ?>
                <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section selected"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="../dotazione_mancante/dotazione_mancante.php"><div class="section"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>  
        </div>
        
        <div class="content"> <!-- Contenuto principale -->
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Pulsante "indietro" usa javascript history -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Pulsante logout, porta alla pagina di logout -->
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            
            <h1>Dotazioni eliminate</h1> <!-- Titolo pagina -->

            <div class="actions">
                <input type="text" id="filterInput" placeholder="Cerca per nome o codice" class="filter-input">
            </div>
            
            <div class="lista-dotazioni"> <!-- Tabella lista dotazioni eliminate -->
                <table>
                    <thead>
                        <td>Codice</td>
                        <td>Nome</td>
                        <td>Categoria</td>
                        <td>Descrizione</td>
                        <td>Prezzo Stimato</td>
                        <td style="text-align: center;">Azioni</td>
                    </thead>
                    <tbody>
                        <?php
                            // Esegue query per prendere tutte le dotazioni con ID_aula NULL e stato 'scartato'
                            $stmt = $conn->query("SELECT * FROM dotazione WHERE ID_aula IS NULL AND stato LIKE 'scartato'");
                            // Recupera tutte le righe come array associativo
                            $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Cicla ogni dotazione per costruire la riga della tabella
                            foreach ($dotazioni as $dotazione) {  
                                echo "<tr>";
                                    echo "<td>".$dotazione['codice']."</td>"; // Codice dotazione
                                    echo "<td>".$dotazione['nome']."</td>"; // Nome dotazione
                                    echo "<td>".$dotazione['categoria']."</td>"; // Categoria
                                    echo "<td>".$dotazione['descrizione']."</td>"; // Descrizione
                                    echo "<td>".$dotazione['prezzo_stimato']."€</td>"; // Prezzo stimato con simbolo €
                                    ?>
                                    <td>
                                        <div class="div-action-btn">
                                            <!-- Link alla pagina modifica dotazione, passa codice e parametro start=archivio -->
                                            <a href="..\lista_dotazione\modifica_dotazione\modifica_dotazione.php?codice=<?php echo $dotazione['codice']?>&start=archivio">
                                                <button name="modifica" class="btn-action btn-green" value="">
                                                    <i class="fas fa-pen"></i> <!-- Icona matita per modifica -->
                                                </button>
                                            </a>
                                            <!-- Form per riabilitare la dotazione (aggiorna con POST) -->
                                            <form method="POST">
                                                <!-- Pulsante che invia il codice dotazione come valore di "abilita" -->
                                                <button name="abilita" value="<?php echo $dotazione['codice']?>" class="btn-action btn-red">
                                                    <i class="fas fa-undo"></i> <!-- Icona freccia undo -->
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
