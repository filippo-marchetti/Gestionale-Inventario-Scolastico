<?php
    session_start();

    //info database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $username = $_SESSION['username'];
    $role = $_SESSION['role'];

    $pk_utente = "";
    $scelta = "";

    if(!is_null($username) && $role == "admin"){
        try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connessione fallita: " . $e->getMessage());
        }
        if (isset($_POST['accetta'])) {
            $pk_utente = $_POST['accetta'];
            $scelta = "attivo";

            //Richiesta accettata e cambio dello stato in attivo
            $stmt = $conn->prepare("UPDATE utente SET stato = :stato WHERE username = :username");
            $stmt->bindParam(':stato', $scelta);
            $stmt->bindParam(':username', $pk_utente);
            $stmt->execute();

            header("Location: " . $_SERVER['PHP_SELF']);
        }else if (isset($_POST['rifiuta'])) {
            $pk_utente = $_POST['rifiuta'];

            //Richiesta negata e eliminazione dell'account
            $stmt = $conn->prepare("DELETE FROM utente WHERE username = :username");
            $stmt->bindParam(':username', $pk_utente);
            $stmt->execute();

            header("Location: " . $_SERVER['PHP_SELF']);
        }
        if (isset($_POST['accept-all'])) {
            $scelta = "attivo";
            $stmt = $conn->prepare("UPDATE utente SET stato = :stato WHERE stato = 'attesa'");
            $stmt->bindParam(':stato', $scelta);
            $stmt->execute();

            header("Location: " . $_SERVER['PHP_SELF']);
        } else if (isset($_POST['reject-all'])) {
            $stmt = $conn->prepare("DELETE FROM utente WHERE stato = 'attesa'");
            $stmt->execute();

            header("Location: " . $_SERVER['PHP_SELF']);
        }

    }else{
        header("Location: ..\logout\logout.php");
    }
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="..\..\assets\css\background.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_style_user_admin.css">
        <link rel="stylesheet" href="..\..\assets\css\shared_admin_subpages.css">
        <link rel="stylesheet" href="user_accept.css">
        <title>Document</title>
        <!-- Font Awesome per icone-->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    </head>
    <body>
        <div class="container">
            <!-- sidebar -->
            <div class="sidebar">
                <div class="image"><img src="..\..\assets\images\logo_darzo.png" width="120px"></div>
                <!-- questa div conterrà i link delle schede -->
                <div class="section-container">
                    <br>
                    <a href="..\admin_page.php"><div class="section"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="..\user_accept\user_accept.php"><div class="section selected"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="..\lista_dotazione\lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout">
                    <a class="logout-btn" href="..\..\logout\logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
                <h1>Richieste Account</h1>

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
                                // Query per recuperare gli account in richiesta
                                $stmt = $conn->query("SELECT * FROM utente WHERE stato = 'attesa'");
                                $utenti = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                                                    <button type="submit" name="accetta" value="<?php echo $utente['username']?>" class="btn-action btn-blu">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    
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