<?php
    session_start(); // Avvia la sessione per gestire i dati dell'utente

    // Configurazione dati per la connessione al database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    // Recupera le variabili di sessione username e ruolo
    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    // Verifica se l'utente è autenticato (username non nullo)
    if(!is_null($username)){
        try {
            // Crea una nuova connessione PDO al database MySQL
            $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
            // Imposta l'errore PDO su eccezioni
            $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            // In caso di errore di connessione, termina lo script mostrando l'errore
            die("Connessione fallita: " . $e->getMessage());
        }

        // Se è stato premuto il pulsante con nome "abilita" (submit del form)
        if(isset($_POST["abilita"])){
            // Prepara la query per aggiornare la dotazione: imposta ID_aula a NULL e stato a 'archiviato'
            $stmt = $conn->prepare("UPDATE dotazione SET ID_aula = NULL, stato = 'archiviato' WHERE codice = :codice");
            // Associa il parametro :codice al valore inviato tramite POST
            $stmt->bindParam(':codice', $_POST['abilita']);
            // Esegue la query di aggiornamento
            $stmt->execute();
        }
    } else {
        // Se username è nullo, redireziona alla pagina di logout (quindi fuori dal sistema)
        header("Location: ..\..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"> <!-- Definisce la codifica dei caratteri -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Permette responsive design -->
    
    <!-- Collegamenti ai fogli di stile CSS esterni -->
    <link rel="stylesheet" href="..\..\assets\css\background.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
    <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">
    <link rel="stylesheet" href="lista_dotazione.css">

    <title>Dotazione Mancante</title> <!-- Titolo pagina mostrato sulla scheda -->

    <!-- Import Font Awesome per icone -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <!-- Import script JS per funzionalità pagina dotazione -->
    <script src="..\lista_dotazione\lista_dotazione.js"></script>
</head>
<body>
    <div class="container"> <!-- Contenitore principale -->
        <!-- Sidebar laterale di navigazione -->
        <div class="sidebar">
            <div class="image">
                <img src="..\..\assets\images\logo_darzo.png" width="120px"> <!-- Immagine logo o placeholder -->
            </div>

            <!-- Contenitore dei link di navigazione -->
            <div class="section-container">
                <br>
                <?php
                    // Se utente admin mostra link HOME admin, altrimenti utente normale
                    if($role == 'admin') {
                        echo '<a href="../admin_page/admin_page/admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    } else {
                        echo '<a href="../user_page/user_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>';
                    }
                ?>
                <!-- Link fissi alle altre sezioni -->
                <a href="../aule/aule.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>

                <?php
                    // Solo se utente è admin, mostra link per gestire tecnici, conferma utenti, nuovo admin
                    if($role == "admin"){
                        echo '<a href="..\admin_page\mostra_user_attivi\mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>';
                        echo '<a href="..\admin_page\user_accept\user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>';
                        echo '<a href="..\admin_page\nuovo_admin\nuovo_admin.php"><div class="section"><span class="section-text"><i class="fas fa-user-shield"></i>CREA NUOVO ADMIN</span></div></a>';
                    };
                ?>

                <!-- Altri link di navigazione -->
                <a href="../lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                <a href="../dotazione_archiviata/dotazione_archiviata.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                <a href="../dotazione_eliminata/dotazione_eliminata.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                <a href="dotazione_mancante.php"><div class="section selected"><span class="section-text"><i class="fas fa-exclamation-triangle"></i>DOTAZIONE MANCANTE</span></div></a>
                <a href="../impostazioni/impostazioni.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
            </div>  
        </div>

        <!-- Contenuto principale della pagina -->
        <div class="content">
            <!-- Barra superiore con pulsanti indietro e logout -->
            <div class="logout" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                <!-- Bottone indietro usa javascript per tornare alla pagina precedente -->
                <a class="back-btn" href="javascript:history.back();" style="display:inline-block;">
                    <i class="fas fa-chevron-left"></i>
                </a>
                <!-- Bottone logout che porta alla pagina di logout -->
                <a class="logout-btn" href="../../logout/logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>

            <h1>Dotazioni Mancanti</h1> <!-- Titolo pagina -->

            <!-- Tabella che mostra le dotazioni mancanti -->
            <div class="lista-dotazioni">
                <table>
                    <thead>
                        <td>Codice</td>
                        <td>Nome</td>
                        <td>Categoria</td>
                        <td>Descrizione</td>
                        <td>Prezzo Stimato</td>
                        <td>Ultima Aula</td>
                        <td style="text-align: center;">Azioni</td>
                    </thead>
                    <tbody>
                        <?php
                            // Esegue la query per selezionare tutte le dotazioni con stato 'mancante'
                            $stmt = $conn->query("SELECT * FROM dotazione WHERE stato LIKE 'mancante'");
                            // Recupera tutti i risultati in un array associativo
                            $dotazioni = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            // Cicla ogni dotazione e stampa una riga della tabella
                            foreach ($dotazioni as $dotazione) {  
                                echo "<tr>";
                                    echo "<td>".$dotazione['codice']."</td>"; // Codice univoco dotazione
                                    echo "<td>".$dotazione['nome']."</td>"; // Nome dotazione
                                    echo "<td>".$dotazione['categoria']."</td>"; // Categoria
                                    echo "<td>".$dotazione['descrizione']."</td>"; // Descrizione
                                    echo "<td>".$dotazione['prezzo_stimato']."€</td>"; // Prezzo stimato con simbolo €
                                    echo "<td>".$dotazione['ID_aula']."</td>"; // Ultima aula a cui apparteneva
                                    ?>
                                    <td>
                                        <div class="div-action-btn">
                                            <!-- Form per azione di riabilitazione dotazione -->
                                            <form method="POST">
                                                <!-- Pulsante che invia codice dotazione per aggiornare lo stato -->
                                                <button name="abilita" value="<?php echo $dotazione['codice']?>" class="btn-action btn-blu">
                                                    <i class="fas fa-undo"></i> <!-- Icona undo -->
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
