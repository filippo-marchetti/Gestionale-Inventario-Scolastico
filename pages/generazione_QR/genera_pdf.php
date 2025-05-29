<?php
require_once('phpqrcode/qrlib.php');
require_once('tcpdf/tcpdf.php');

$data = $_GET['id'] ?? '';
if (empty($data)) {
    die("Errore: Nessun dato fornito.");
}

// Parametri
$size = 4;
$fontSize = 12;
$textPadding = 6;

// Crea QR temporaneo
$tempQr = tempnam(sys_get_temp_dir(), 'qr_') . '.png';
QRcode::png($data, $tempQr, QR_ECLEVEL_L, $size);
$qrImage = imagecreatefrompng($tempQr);
$qrWidth = imagesx($qrImage);
$qrHeight = imagesy($qrImage);

// Font
$fontPath = __DIR__ . '/ARIAL.TTF';
if (!is_file($fontPath)) {
    unlink($tempQr);
    die("Errore: Font non trovato.");
}

// Calcola bounding box del testo
$bbox = imagettfbbox($fontSize, 0, $fontPath, $data);
$textWidth = abs($bbox[2] - $bbox[0]);
$textHeight = abs($bbox[7] - $bbox[1]);

// Dimensioni immagine finale
$finalWidth = max($qrWidth, $textWidth + 36);
$finalHeight = $qrHeight + $textHeight + $textPadding + 24;

$finalImage = imagecreatetruecolor($finalWidth, $finalHeight);
$white = imagecolorallocate($finalImage, 255, 255, 255);
$black = imagecolorallocate($finalImage, 0, 0, 0);
imagefill($finalImage, 0, 0, $white);

// Copia QR centrato
$xQr = (int)(($finalWidth - $qrWidth) / 2);
imagecopy($finalImage, $qrImage, $xQr, 0, 0, 0, $qrWidth, $qrHeight);

// Larghezza media carattere
$charWidth = $textWidth / max(strlen($data), 1);

// Rettangolo fisso per 8 caratteri
$fixedCharCount = 8;
$rectWidth = (int)(($charWidth * $fixedCharCount) + 2 * $textPadding);
$rectHeight = $textHeight + 2 * $textPadding;

// Posizione rettangolo
$rectX = (int)($xQr + ($qrWidth - $rectWidth) / 2);
$rectY = $qrHeight + 4;

// Disegna rettangolo
imagefilledrectangle($finalImage, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $white);
imagerectangle($finalImage, $rectX, $rectY, $rectX + $rectWidth, $rectY + $rectHeight, $black);

// Testo centrato
$textX = (int)($rectX + ($rectWidth - $textWidth) / 2);
$textY = (int)($rectY + $textPadding + $textHeight);
imagettftext($finalImage, $fontSize, 0, $textX, $textY, $black, $fontPath, $data);

// Salva PNG temporaneo
$tempPng = tempnam(sys_get_temp_dir(), 'qr_img_') . '.png';
imagepng($finalImage, $tempPng);

// Pulizia immagine
imagedestroy($qrImage);
imagedestroy($finalImage);
unlink($tempQr);

// === TCPDF ===
$pdf = new TCPDF();
$pdf->setPrintHeader(false);
$pdf->SetMargins(10, 10, 10);
$pdf->AddPage();

$x = 10;
$y = 10;
$imgWidthMM = 40;
$imgHeightMM = 50;

// Inserisci immagine nel PDF
$pdf->Image($tempPng, $x, $y, $imgWidthMM, $imgHeightMM, 'PNG');

// Bordo tratteggiato attorno al blocco
$pdf->SetDrawColor(0, 0, 0);
$pdf->SetLineStyle(['width' => 0.3, 'dash' => '2,2']);
$pdf->Rect($x - 1.5, $y - 1.5, $imgWidthMM + 3, $imgHeightMM + 3);

// Rimuovi immagine temporanea
unlink($tempPng);

// Output PDF
$pdf->Output("QRCode_$data.pdf", 'I');
exit;
