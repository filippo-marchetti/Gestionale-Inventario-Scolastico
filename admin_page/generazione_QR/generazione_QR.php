<?php 
include('phpqrcode/qrlib.php');

$data = $_GET['id'] ?? '';
$size = 10;

if (!empty($data)) {
    // Crea QR temporaneo
    $tempQr = tempnam(sys_get_temp_dir(), 'qr_');
    QRcode::png($data, $tempQr, QR_ECLEVEL_L, $size);

    $qrImage = imagecreatefrompng($tempQr);
    $qrWidth = imagesx($qrImage);
    $qrHeight = imagesy($qrImage);

    // Imposta font
    $fontSize = 30; // ATTENZIONE: usare valori realistici, tipo 14-30
    $fontPath = __DIR__ . '/ARIAL.TTF'; // assicurati che esista
    $textPadding = 20;

    if (!file_exists($fontPath)) {
        die("Errore: Font non trovato");
    }

    // Calcola bounding box
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $data);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];

    // Altezza finale dell'immagine
    $finalHeight = $qrHeight + $textHeight + $textPadding;
    $finalImage = imagecreatetruecolor($qrWidth, $finalHeight);

    // Sfondo bianco
    $white = imagecolorallocate($finalImage, 255, 255, 255);
    imagefill($finalImage, 0, 0, $white);

    // Copia QR
    imagecopy($finalImage, $qrImage, 0, 0, 0, 0, $qrWidth, $qrHeight);

    // Colore del testo
    $black = imagecolorallocate($finalImage, 0, 0, 0);

    // Calcola posizione centrata del testo
    $x = ($qrWidth - $textWidth) / 2;
    $y = $qrHeight + $textPadding + $textHeight;

    // Scrivi il testo
    imagettftext($finalImage, $fontSize, 0, $x, $y, $black, $fontPath, $data);

    // Output base64
    ob_start();
    imagepng($finalImage);
    $Qr = ob_get_contents();
    ob_end_clean();

    imagedestroy($qrImage);
    imagedestroy($finalImage);
    unlink($tempQr);

    $Qr64 = base64_encode($Qr);
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
        <link rel="stylesheet" href="..\..\assets\css\shared_lista_dotazione.css">
        <link rel="stylesheet" href="generazione_QR.css">
        <title>QR - Page</title>
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
                    <a href="admin_page.php"><div class="section selected"><span class="section-text"><i class="fas fa-home"></i> HOME</span></div></a>
                    <a href="boh.php"><div class="section"><span class="section-text"><i class="fas fa-clipboard-list"></i> INVENTARI</span></div></a>
                    <a href="mostra_user_attivi/mostra_user_attivi.php"><div class="section"><span class="section-text"><i class="fas fa-user"></i> TECNICI</span></div></a>
                    <a href="user_accept/user_accept.php"><div class="section"><span class="section-text"><i class="fas fa-user-check"></i>CONFERMA UTENTI</span></div></a>
                    <a href="lista_dotazione/lista_dotazione.php"><div class="section"><span class="section-text"><i class="fas fa-boxes-stacked"></i>DOTAZIONE</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-warehouse"></i>MAGAZZINO</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-trash"></i>STORICO SCARTI</span></div></a>
                    <a href="bop.php"><div class="section"><span class="section-text"><i class="fas fa-cogs"></i>IMPOSTAZIONI</span></div></a>
                </div>  
            </div>
            <!-- content contiene tutto ciò che è al di fuori della sidebar -->
            <div class="content">
                <!-- user-logout contiene il nome utente dell'utente loggato e il collegamento per il logout -->
                <div class="logout">
                    <a class="logout-btn" href="..\logout\logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>

                <h1>QR Code: <?php echo $data?></h1>

                <div class="show-qr">
                    <?php
                        // Mostra l'immagine
                        echo "<img class='qr-code' src='data:image/png;base64,$Qr64'><br>";
                    ?>
                    <div class="corner-bl"></div>
                    <div class="corner-br"></div>
                </div>
        </div>
    </body>
</html>