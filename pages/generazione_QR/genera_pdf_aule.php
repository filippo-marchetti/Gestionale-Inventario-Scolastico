<?php
    session_start();

    require_once('phpqrcode/qrlib.php');
    require_once('tcpdf/tcpdf.php');

    // DB info
    $host = 'localhost';
    $db = 'inventariosdarzo';
    $user = 'root';
    $pass = '';

    try {
        $conn = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connessione fallita: " . $e->getMessage());
    }

    $id_aula = $_GET['ID_aula'] ?? '';
    if (empty($id_aula)) {
        die("Errore: Nessun ID aula fornito.");
    }

    // Prendi tutti i codici di dotazione per questa aula
    $sql = "SELECT codice FROM dotazione WHERE ID_aula = :id_aula";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':id_aula' => $id_aula]);
    $dotazioni = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (!$dotazioni) {
        die("Nessuna dotazione trovata per l'aula $id_aula.");
    }

    // Parametri
    $size = 4; // dimensione QR
    $fontSize = 12;
    $textPadding = 6;
    $fontPath = __DIR__ . '/ARIAL.TTF';
    if (!file_exists($fontPath)) {
        die("Errore: Font non trovato");
    }

    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    // Dimensioni immagine (in mm) e spaziatura
    $imgWidthMM = 40;
    $imgHeightMM = 50;
    $spaceX = 10;
    $spaceY = 10;
    $cols = 3;
    $row = 0;
    $col = 0;

    foreach ($dotazioni as $data) {
        $tempQr = tempnam(sys_get_temp_dir(), 'qr_');
        QRcode::png($data, $tempQr, QR_ECLEVEL_L, $size);
        $qrImage = imagecreatefrompng($tempQr);
        $qrWidth = imagesx($qrImage);
        $qrHeight = imagesy($qrImage);

        // Usa imageftbbox (invece di imagettfbbox)
        $bbox = imageftbbox($fontSize, 0, $fontPath, $data);
        $textWidth = $bbox[2] - $bbox[0];
        $textHeight = $bbox[1] - $bbox[7];

        $finalWidth = max($qrWidth, $textWidth + 2 * $textPadding + 24);
        $finalHeight = $qrHeight + $textHeight + $textPadding + 24;

        $finalImage = imagecreatetruecolor($finalWidth, $finalHeight);
        $white = imagecolorallocate($finalImage, 255, 255, 255);
        imagefill($finalImage, 0, 0, $white);
        $black = imagecolorallocate($finalImage, 0, 0, 0);

        $xQr = ($finalWidth - $qrWidth) / 2;
        imagecopy($finalImage, $qrImage, $xQr, 0, 0, 0, $qrWidth, $qrHeight);

        $charWidth = strlen($data) > 0 ? $textWidth / strlen($data) : $textWidth;

        $fixedCharCount = 8;
        $rectWidth = ($charWidth * $fixedCharCount) + 2 * $textPadding;
        $rectHeight = $textHeight + 2 * $textPadding;

        $rectX = $xQr + ($qrWidth - $rectWidth) / 2;
        $rectY = $qrHeight + 4;

        imagefilledrectangle($finalImage, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $white);
        imagerectangle($finalImage, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $black);

        $textX = $rectX + ($rectWidth - $textWidth) / 2;
        $textY = $rectY + $textPadding + $textHeight;

        // Usa imagefttext (invece di imagettftext)
        imagefttext($finalImage, $fontSize, 0, $textX, $textY, $black, $fontPath, $data);

        ob_start();
        imagepng($finalImage);
        $imageData = ob_get_clean();

        imagedestroy($qrImage);
        imagedestroy($finalImage);
        unlink($tempQr);

        $tempPng = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
        file_put_contents($tempPng, $imageData);

        $posX = 10 + $col * ($imgWidthMM + $spaceX);
        $posY = 10 + $row * ($imgHeightMM + $spaceY);

        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineStyle(['width' => 0.3, 'dash' => '2,2']);
        $pdf->Rect($posX - 1.5, $posY - 1.5, $imgWidthMM + 3, $imgHeightMM + 3);

        $pdf->Image($tempPng, $posX, $posY, $imgWidthMM, $imgHeightMM, 'PNG');

        unlink($tempPng);

        $col++;
        if ($col >= $cols) {
            $col = 0;
            $row++;
        }

        if (($posY + $imgHeightMM + $spaceY) > ($pdf->getPageHeight() - 10)) {
            $pdf->AddPage();
            $row = 0;
            $col = 0;
        }
    }

    $pdf->Output("QRCode_Aula_$id_aula.pdf", 'I');
exit;