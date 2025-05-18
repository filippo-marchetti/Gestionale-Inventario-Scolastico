<?php 
include('phpqrcode/qrlib.php');

$data = $_GET['id'] ?? '';
$size = 5; // QR più piccolo
$fontSize = 16;
$textPadding = 4;

if (!empty($data)) {
    // Crea QR temporaneo
    $tempQr = tempnam(sys_get_temp_dir(), 'qr_');
    QRcode::png($data, $tempQr, QR_ECLEVEL_L, $size);

    $qrImage = imagecreatefrompng($tempQr);
    $qrWidth = imagesx($qrImage);
    $qrHeight = imagesy($qrImage);

    // Imposta font
    $fontPath = __DIR__ . '/ARIAL.TTF'; // assicurati che esista

    if (!file_exists($fontPath)) {
        die("Errore: Font non trovato");
    }

    // Calcola bounding box
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $data);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];

    // Altezza e larghezza finale
    $finalWidth = max($qrWidth, $textWidth + 36);
    $finalHeight = $qrHeight + $textHeight + $textPadding + 24;

    $finalImage = imagecreatetruecolor($finalWidth, $finalHeight);

    // Sfondo bianco
    $white = imagecolorallocate($finalImage, 255, 255, 255);
    imagefill($finalImage, 0, 0, $white);

    // Copia QR centrato in alto
    $xQr = ($finalWidth - $qrWidth) / 2;
    imagecopy($finalImage, $qrImage, $xQr, 0, 0, 0, $qrWidth, $qrHeight);

    // Colore del testo e rettangolo
    $black = imagecolorallocate($finalImage, 0, 0, 0);
    $rectBg = imagecolorallocate($finalImage, 255, 255, 255); // bianco
    $rectBorder = imagecolorallocate($finalImage, 0, 0, 0); // nero pieno

    // Calcola posizione centrata del testo
    $x = ($finalWidth - $textWidth) / 2;
    $y = $qrHeight + $textPadding + $textHeight;

    // Rettangolo arrotondato largo quanto il QR code e centrato
    $rectWidth = $qrWidth;
    $rectX1 = ($finalWidth - $rectWidth) / 2;
    $rectX2 = $rectX1 + $rectWidth;
    $rectPaddingY = 10; // padding verticale aumentato
    $rectY1 = $y - $textHeight - $rectPaddingY;
    $rectY2 = $y + $rectPaddingY;
    $radius = 10;

    // Funzione rettangolo arrotondato pieno
    function imageRoundedRectangle($im, $x1, $y1, $x2, $y2, $radius, $color) {
        imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
        imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);
        imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
        imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
    }
    // Funzione bordo rettangolo arrotondato spesso
    function imageRoundedRectangleBorder($im, $x1, $y1, $x2, $y2, $radius, $color) {
        imagesetthickness($im, 3); // bordo più spesso
        imageline($im, $x1+$radius, $y1, $x2-$radius, $y1, $color);
        imageline($im, $x1+$radius, $y2, $x2-$radius, $y2, $color);
        imageline($im, $x1, $y1+$radius, $x1, $y2-$radius, $color);
        imageline($im, $x2, $y1+$radius, $x2, $y2-$radius, $color);
        imagearc($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, 180, 270, $color);
        imagearc($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, 270, 360, $color);
        imagearc($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, 90, 180, $color);
        imagearc($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, 0, 90, $color);
        imagesetthickness($im, 1); // ripristina spessore normale
    }

    imageRoundedRectangle($finalImage, $rectX1, $rectY1, $rectX2, $rectY2, $radius, $rectBg);
    imageRoundedRectangleBorder($finalImage, $rectX1, $rectY1, $rectX2, $rectY2, $radius, $rectBorder);

    // Scrivi il testo sopra il rettangolo, centrato
    $textX = $rectX1 + ($rectWidth - $textWidth) / 2;
    imagettftext($finalImage, $fontSize, 0, $textX, $y, $black, $fontPath, $data);

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
<html lang="it">
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

                <h1>QR Code: <?php echo htmlspecialchars($data) ?></h1>

                <div class="show-qr" id="qr-print-area">
                    <div class="print-border">
                        <?php
                            // Mostra solo l'immagine (che ora contiene anche il numero nel quadratino)
                            echo "<img class='qr-code' src='data:image/png;base64,$Qr64'><br>";
                        ?>
                    </div>
                    <div class="corner-bl"></div>
                    <div class="corner-br"></div>
                </div>
                <button class="print-btn" onclick="printQR()"><i class="fas fa-print"></i> Stampa QR</button>
                <script>
                    function printQR() {
                        var printContents = document.getElementById('qr-print-area').innerHTML;
                        var win = window.open('', '', 'width=600,height=800');
                        win.document.write('<html><head><title>Stampa QR</title><link rel="stylesheet" href="generazione_QR.css"></head><body style="background:white;">' + printContents + '</body></html>');
                        win.document.close();
                        win.focus();
                        win.print();
                        win.close();
                    }
                </script>
        </div>
    </body>
</html>