<?php
    require_once('phpqrcode/qrlib.php');
    require_once('tcpdf/tcpdf.php');

    $data = $_GET['id'] ?? '';
    if (empty($data)) {
        die("Errore: Nessun dato fornito.");
    }

    // Parametri
    $size = 4; // QR più piccolo
    $fontSize = 12;
    $textPadding = 6;

    // Crea QR temporaneo
    $tempQr = tempnam(sys_get_temp_dir(), 'qr_');
    QRcode::png($data, $tempQr, QR_ECLEVEL_L, $size);
    $qrImage = imagecreatefrompng($tempQr);
    $qrWidth = imagesx($qrImage);
    $qrHeight = imagesy($qrImage);

    // Font
    $fontPath = __DIR__ . '/ARIAL.TTF';
    if (!file_exists($fontPath)) {
        die("Errore: Font non trovato");
    }

    // Bounding box testo
    $bbox = imagettfbbox($fontSize, 0, $fontPath, $data);
    $textWidth = $bbox[2] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[7];

    // Calcola larghezza immagine finale
    $finalWidth = max($qrWidth, $textWidth + 36);
    $finalHeight = $qrHeight + $textHeight + $textPadding + 24;

    $finalImage = imagecreatetruecolor($finalWidth, $finalHeight);
    $white = imagecolorallocate($finalImage, 255, 255, 255);
    imagefill($finalImage, 0, 0, $white);

    // Colori
    $black = imagecolorallocate($finalImage, 0, 0, 0);

    // Copia QR centrato
    $xQr = ($finalWidth - $qrWidth) / 2;
    imagecopy($finalImage, $qrImage, $xQr, 0, 0, 0, $qrWidth, $qrHeight);

    // Calcola larghezza media di un carattere (basato sulla larghezza del testo attuale)
    $charWidth = $textWidth / strlen($data);

    // Larghezza fissa rettangolo per 8 caratteri
    $fixedCharCount = 8;
    $rectWidth = ($charWidth * $fixedCharCount) + 2 * $textPadding;

    // Altezza come prima
    $rectHeight = $textHeight + 2 * $textPadding;

    // Posizione rettangolo: centrato rispetto al QR code (non all’intera immagine)
    $rectX = $xQr + ($qrWidth - $rectWidth) / 2;
    $rectY = $qrHeight + 4;

    // Rettangolo sotto QR per codice (fisso)
    imagefilledrectangle($finalImage, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $white);
    imagerectangle($finalImage, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $black);

    // Testo dentro il rettangolo (centrato orizzontalmente)
    $textX = $rectX + ($rectWidth - $textWidth) / 2;
    $textY = $rectY + $textPadding + $textHeight;
    imagettftext($finalImage, $fontSize, 0, $textX, $textY, $black, $fontPath, $data);

    // Codifica immagine
    ob_start();
    imagepng($finalImage);
    $imageData = ob_get_clean();
    unlink($tempQr);
    imagedestroy($qrImage);
    imagedestroy($finalImage);

    // Salva PNG temporaneo
    $tempPng = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
    file_put_contents($tempPng, $imageData);

    // === TCPDF ===
    $pdf = new TCPDF();
    $pdf->setPrintHeader(false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->AddPage();

    // Coordinate in alto a sinistra
    $x = 10; // distanza dal bordo sinistro
    $y = 10; // distanza dall’alto
    $imgWidthMM = 40; // ancora più piccolo
    $imgHeightMM = 50; // proporzionato

    // Inserisci immagine
    $pdf->Image($tempPng, $x, $y, $imgWidthMM, $imgHeightMM, 'PNG');

    // Bordo tratteggiato attorno a tutto il blocco (QR + rettangolo codice)
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineStyle(['width' => 0.3, 'dash' => '2,2']);
    $pdf->Rect($x - 1.5, $y - 1.5, $imgWidthMM + 3, $imgHeightMM + 3);

    // Pulizia
    unlink($tempPng);
    $pdf->Output("QRCode_$data.pdf", 'I');
exit;
