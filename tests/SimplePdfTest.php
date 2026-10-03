<?php

require_once __DIR__ . '/../app/helpers/SimplePdf.php';

$pdf = new SimplePdf();
$pdf->addPage([
    SimplePdf::rectangle(10, 10, 100, 20, [0.7, 0.02, 0.04]),
    SimplePdf::text(20, 20, 'Relatório de admissões', 12, true),
]);
$pdf->addPage([SimplePdf::text(20, 800, 'Página 2')]);
$output = $pdf->render();

if (substr($output, 0, 8) !== "%PDF-1.4" || substr($output, -5) !== '%%EOF') {
    throw new RuntimeException('The generated document is not a valid PDF envelope.');
}
if (substr_count($output, '/Type /Page ') !== 2 || strpos($output, '/Count 2') === false) {
    throw new RuntimeException('The generated PDF does not contain the expected pages.');
}
if (strpos($output, '/Helvetica-Bold') === false || strpos($output, 'xref') === false) {
    throw new RuntimeException('The generated PDF is missing required resources.');
}

fwrite(STDOUT, "Simple PDF test passed.\n");
