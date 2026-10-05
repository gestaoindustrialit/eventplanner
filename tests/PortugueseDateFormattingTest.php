<?php

require_once __DIR__ . '/../app/helpers/date.php';

$expected = [
    1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun',
    7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez',
];

foreach ($expected as $month => $abbreviation) {
    $date = sprintf('2026-%02d-04', $month);
    $actual = formatPtPtShortDate($date);
    if ($actual !== '04 ' . $abbreviation . ' 2026') {
        fwrite(STDERR, "FAIL: {$date} was formatted as {$actual}.\n");
        exit(1);
    }
}

if (formatPtPtShortDate('2026-12-04', false, true) !== '04 DEZ') {
    fwrite(STDERR, "FAIL: The uppercase short December date is not in PT-PT.\n");
    exit(1);
}

fwrite(STDOUT, "PT-PT date formatting test passed.\n");
