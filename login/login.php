<?php
    //info database
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    $errors = [];   //array contenente i possibili errori di inserimento

    //parametri che verranno poi riempiti dall'utente
    $username = '';
    $password = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    if (isset($_POST["submit"]) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        // Recupero dei dati inseriti nel form
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        //Collezzione degli errori opportuni
        if (!$username) $errors['username'] = "Inserisci il nome utente.";
        if (!$password) $errors['password'] = "Inserisci la password.";

        if ($username && $password) { //controllo inserimento
            // Query per recuperare i dati dell'utente
            $stmt = $conn->prepare("SELECT * FROM utente WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            //Controllo se l'utente esiste, se non viene trovato niente in utente
            //controllo che le credenziali siano di un admin
            if($user){
                //verifica della password utente
                if (password_verify($password, password_hash($user['password'], PASSWORD_DEFAULT))){
                    header('Location: ..\user_page\user_page.php');
                } else{
                    $errors['username'] = "Password o nome utente errati";
                }
            }else{
                //recupero delle informazioni degli admin
                $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
                $stmt->execute([$username]);
                $admin = $stmt->fetch();
                
                //verifica della password admin
                if ($admin && password_verify($password, password_hash($admin['password'], PASSWORD_DEFAULT))){
                    header('Location: ..\admin_page\admin_page.php');
                }else{
                    $errors['username'] = "Password o nome utente errati";
                }
            }
        }
    }

?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Login - D'Arzo</title>
    <link rel="stylesheet" href="..\assets\css\shared_style_login_register.css">
    <link rel="stylesheet" href="..\assets\css\background.css">
    <link rel="stylesheet" href="style_login.css">
    <!-- Font Awesome per icone-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="form">
        <div class="image">
            <img src="..\assets\images\placeholder.png" width="120px">
        </div>
    
        <h1>Login</h1>

        <form method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
            <!-- input username con aggiunta dell'icona -->
            <i class="fas fa-user"></i>
            <label>Username</label>
            <input type="text" name="username" id="username" class="<?= isset($errors['username']) ? 'input-error' : '' ?>">
            <?php if (isset($errors['username'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['username'] ?></small>
            <?php endif; ?>

            <!-- input password con aggiunta dell'icona -->
            <i class="fas fa-lock"></i>
            <label>Password</label>
            <input type="password" name="password" id="password" class="<?= isset($errors['password']) ? 'input-error' : '' ?>">
            <?php if (isset($errors['password'])): ?>
                <small class="error"><i class="fas fa-exclamation-circle"></i><?= $errors['password'] ?></small>
            <?php endif; ?>
            
            <!-- input submit -->
            <input type="submit" name="submit" value="Accedi">
        </form>
        <p id="registrati">Non hai un account? <a href="..\Register\register.php">Registrati</a></p>
    </div>
</body>
</html>
