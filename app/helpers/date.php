<?php

/**
 * Formats a calendar date with the abbreviated month names used in PT-PT.
 */
function formatPtPtShortDate(string $date, bool $includeYear = true, bool $uppercaseMonth = false): string
{
    $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $errors = DateTimeImmutable::getLastErrors();
    if ($dateTime === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $dateTime->format('Y-m-d') !== $date) {
        return $date;
    }

    $months = [
        1 => 'Jan',
        2 => 'Fev',
        3 => 'Mar',
        4 => 'Abr',
        5 => 'Mai',
        6 => 'Jun',
        7 => 'Jul',
        8 => 'Ago',
        9 => 'Set',
        10 => 'Out',
        11 => 'Nov',
        12 => 'Dez',
    ];
    $month = $months[(int)$dateTime->format('n')];
    if ($uppercaseMonth) {
        $month = strtoupper($month);
    }

    return $dateTime->format('d') . ' ' . $month . ($includeYear ? ' ' . $dateTime->format('Y') : '');
}
