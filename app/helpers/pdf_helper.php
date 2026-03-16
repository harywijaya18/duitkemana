<?php

function generate_simple_pdf(string $title, array $lines): string
{
    $content = "BT /F1 12 Tf 50 780 Td (" . pdf_escape($title) . ") Tj ET\n";
    $y = 760;

    foreach ($lines as $line) {
        $content .= "BT /F1 10 Tf 50 {$y} Td (" . pdf_escape($line) . ") Tj ET\n";
        $y -= 16;
        if ($y < 60) {
            break;
        }
    }

    $objects = [];
    $objects[] = '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj';
    $objects[] = '2 0 obj << /Type /Pages /Count 1 /Kids [3 0 R] >> endobj';
    $objects[] = '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >> endobj';
    $objects[] = '4 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj';
    $objects[] = '5 0 obj << /Length ' . strlen($content) . ' >> stream\n' . $content . 'endstream endobj';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];

    foreach ($objects as $obj) {
        $offsets[] = strlen($pdf);
        $pdf .= $obj . "\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= 'xref\n0 ' . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";

    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf('%010d 00000 n ', $offsets[$i]) . "\n";
    }

    $pdf .= 'trailer << /Size ' . (count($objects) + 1) . ' /Root 1 0 R >>\n';
    $pdf .= 'startxref\n' . $xrefOffset . "\n%%EOF";

    return $pdf;
}

function pdf_escape(string $text): string
{
    $text = str_replace('\\', '\\\\', $text);
    $text = str_replace('(', '\\(', $text);
    return str_replace(')', '\\)', $text);
}
