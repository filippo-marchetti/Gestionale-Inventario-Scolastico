<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: ../../logout/logout.php");
    exit();
}

$idAula = $_GET['id'] ?? null;
if (!$idAula) {
    die("ID aula non specificato.");
}

// Connessione DB
$host = 'localhost';
$db = 'inventariosdarzo';
$user = 'root';
$pass = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT codice FROM dotazione WHERE ID_aula = ?");
    $stmt->execute([$idAula]);
    $codici = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Errore DB: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>QR Dotazioni Aula <?php echo htmlspecialchars($idAula); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="generazione_QR_aula.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="content">
            <h1>QR Dotazioni Aula <?php echo htmlspecialchars($idAula); ?></h1>
            <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Stampa tutti i QR</button>
            <div class="qr-list">
                <?php
                // Mostra i QR SOLO in stampa
                echo '<div class="only-print">';
                foreach ($codici as $codice) {
                    $size = 8;
                    $fontSize = 22;
                    $textPadding = 8;
                    $fontPath = __DIR__ . '/../../../admin_page/generazione_QR/ARIAL.TTF';

                    include_once('../../../admin_page/generazione_QR/phpqrcode/qrlib.php');
                    $tempQr = tempnam(sys_get_temp_dir(), 'qr_');
                    QRcode::png($codice, $tempQr, QR_ECLEVEL_L, $size);

                    $qrImage = imagecreatefrompng($tempQr);
                    $qrWidth = imagesx($qrImage);
                    $qrHeight = imagesy($qrImage);

                    $bbox = imagettfbbox($fontSize, 0, $fontPath, $codice);
                    $textWidth = $bbox[2] - $bbox[0];
                    $textHeight = $bbox[1] - $bbox[7];

                    $finalWidth = max($qrWidth, $textWidth + 36);
                    $finalHeight = $qrHeight + $textHeight + $textPadding + 24;

                    $finalImage = imagecreatetruecolor($finalWidth, $finalHeight);

                    $white = imagecolorallocate($finalImage, 255, 255, 255);
                    imagefill($finalImage, 0, 0, $white);

                    $xQr = ($finalWidth - $qrWidth) / 2;
                    imagecopy($finalImage, $qrImage, $xQr, 0, 0, 0, $qrWidth, $qrHeight);

                    $black = imagecolorallocate($finalImage, 0, 0, 0);
                    $rectBg = imagecolorallocate($finalImage, 255, 255, 255);
                    $rectBorder = imagecolorallocate($finalImage, 0, 0, 0);

                    $x = ($finalWidth - $textWidth) / 2;
                    $y = $qrHeight + $textPadding + $textHeight;

                    $rectWidth = $qrWidth;
                    $rectX1 = ($finalWidth - $rectWidth) / 2;
                    $rectX2 = $rectX1 + $rectWidth;
                    $rectPaddingY = 10;
                    $rectY1 = $y - $textHeight - $rectPaddingY;
                    $rectY2 = $y + $rectPaddingY;
                    $radius = 10;

                    if (!function_exists('imageRoundedRectangle')) {
                        function imageRoundedRectangle($im, $x1, $y1, $x2, $y2, $radius, $color) {
                            imagefilledrectangle($im, $x1+$radius, $y1, $x2-$radius, $y2, $color);
                            imagefilledrectangle($im, $x1, $y1+$radius, $x2, $y2-$radius, $color);
                            imagefilledellipse($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, $color);
                            imagefilledellipse($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, $color);
                            imagefilledellipse($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, $color);
                            imagefilledellipse($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, $color);
                        }
                    }
                    if (!function_exists('imageRoundedRectangleBorder')) {
                        function imageRoundedRectangleBorder($im, $x1, $y1, $x2, $y2, $radius, $color) {
                            imagesetthickness($im, 3);
                            imageline($im, $x1+$radius, $y1, $x2-$radius, $y1, $color);
                            imageline($im, $x1+$radius, $y2, $x2-$radius, $y2, $color);
                            imageline($im, $x1, $y1+$radius, $x1, $y2-$radius, $color);
                            imageline($im, $x2, $y1+$radius, $x2, $y2-$radius, $color);
                            imagearc($im, $x1+$radius, $y1+$radius, $radius*2, $radius*2, 180, 270, $color);
                            imagearc($im, $x2-$radius, $y1+$radius, $radius*2, $radius*2, 270, 360, $color);
                            imagearc($im, $x1+$radius, $y2-$radius, $radius*2, $radius*2, 90, 180, $color);
                            imagearc($im, $x2-$radius, $y2-$radius, $radius*2, $radius*2, 0, 90, $color);
                            imagesetthickness($im, 1);
                        }
                    }

                    imageRoundedRectangle($finalImage, $rectX1, $rectY1, $rectX2, $rectY2, $radius, $rectBg);
                    imageRoundedRectangleBorder($finalImage, $rectX1, $rectY1, $rectX2, $rectY2, $radius, $rectBorder);

                    $textX = $rectX1 + ($rectWidth - $textWidth) / 2;
                    imagettftext($finalImage, $fontSize, 0, $textX, $y, $black, $fontPath, $codice);

                    ob_start();
                    imagepng($finalImage);
                    $Qr = ob_get_contents();
                    ob_end_clean();

                    imagedestroy($qrImage);
                    imagedestroy($finalImage);
                    unlink($tempQr);

                    $Qr64 = base64_encode($Qr);

                    echo '<div class="qr-item print-only"><div class="print-border">';
                    echo "<img class='qr-code' src='data:image/png;base64,$Qr64'><br>";
                    echo '</div></div>';
                }
                echo '</div>';
                ?>
            </div>
        </div>
    </div>
</body>
</html>