<?php

class AdmissionsPdfReport
{
    private const PAGE_WIDTH = 595;
    private const MARGIN = 42;

    public static function render(array $report, array $contacts): string
    {
        $attendees = $report['attendees'] ?? [];
        $chunks = array_chunk($attendees, 12);
        if (empty($chunks)) {
            $chunks = [[]];
        }

        $pages = [];
        foreach ($chunks as $index => $chunk) {
            $pages[] = self::buildPage($report, $contacts, $chunk, $index + 1, count($chunks));
        }

        return self::assemble($pages);
    }

    private static function buildPage(array $report, array $contacts, array $attendees, int $page, int $pageCount): array
    {
        $event = $report['event'];
        $content = "q\n";
        $links = [];

        // Brand header and vector logo: this keeps the report crisp without an external image dependency.
        $content .= self::fill(11, 18, 32) . self::rect(0, 722, self::PAGE_WIDTH, 120, true);
        $content .= self::fill(179, 0, 0) . self::rect(0, 716, self::PAGE_WIDTH, 6, true);
        $content .= self::logoMark(42, 754);
        $content .= self::text('CHORA DE RIR', 96, 792, 22, true, [255, 255, 255]);
        $content .= self::text('PRODUÇÃO  |  BOOKING  |  EXPERIÊNCIAS', 96, 769, 8, false, [204, 213, 229]);
        $content .= self::text('RELATÓRIO DE ADMISSÕES', 553, 792, 9, true, [255, 255, 255], 'right');
        $content .= self::text('Emitido em ' . date('d/m/Y H:i'), 553, 773, 8, false, [204, 213, 229], 'right');

        $content .= self::text((string)$event['title'], 42, 680, 21, true, [23, 29, 38]);
        $eventMeta = self::formatDate((string)$event['date']) . ' às ' . substr((string)$event['time'], 0, 5);
        if (!empty($event['location'])) {
            $eventMeta .= '  |  ' . $event['location'];
        }
        $content .= self::text(self::truncate($eventMeta, 100), 42, 657, 10, false, [86, 96, 112]);

        $cardY = 590;
        $cardWidth = 158;
        $cards = [
            ['RESERVADOS', (string)$report['total'], [23, 29, 38]],
            ['ENTRARAM', (string)$report['admitted'], [22, 130, 78]],
            ['POR ENTRAR', (string)$report['pending'], [179, 0, 0]],
        ];
        foreach ($cards as $index => $card) {
            $x = self::MARGIN + ($index * ($cardWidth + 18));
            $content .= self::fill(248, 249, 251) . self::rect($x, $cardY, $cardWidth, 52, true);
            $content .= self::stroke(226, 230, 236) . self::rect($x, $cardY, $cardWidth, 52, false);
            $content .= self::text($card[0], $x + 12, $cardY + 34, 7, true, [100, 110, 126]);
            $content .= self::text($card[1], $x + 12, $cardY + 11, 19, true, $card[2]);
        }

        $percentage = $report['total'] > 0 ? min(100, round(($report['admitted'] / $report['total']) * 100)) : 0;
        $content .= self::fill(231, 234, 239) . self::rect(42, 568, 511, 7, true);
        $content .= self::fill(22, 130, 78) . self::rect(42, 568, 511 * ($percentage / 100), 7, true);
        $content .= self::text($percentage . '% das entradas validadas', 553, 556, 8, true, [86, 96, 112], 'right');

        $content .= self::text('LISTA DE RESERVAS E CONTACTOS', 42, 525, 10, true, [23, 29, 38]);
        $content .= self::fill(23, 29, 38) . self::rect(42, 494, 511, 25, true);
        $headers = [['PESSOA', 50], ['CONTACTO', 205], ['BILHETES', 425], ['ENTRADAS', 486]];
        foreach ($headers as [$label, $x]) {
            $content .= self::text($label, $x, 503, 7, true, [255, 255, 255]);
        }

        $rowTop = 494;
        foreach ($attendees as $index => $attendee) {
            $rowY = $rowTop - (($index + 1) * 35);
            if ($index % 2 === 1) {
                $content .= self::fill(248, 249, 251) . self::rect(42, $rowY, 511, 35, true);
            }
            $content .= self::stroke(229, 232, 237) . self::line(42, $rowY, 553, $rowY);
            $content .= self::text(self::truncate((string)$attendee['customer_name'], 25), 50, $rowY + 20, 8, true, [23, 29, 38]);
            $content .= self::text(self::truncate((string)$attendee['customer_email'], 34), 205, $rowY + 20, 7, false, [55, 65, 81]);
            $content .= self::text(self::truncate((string)($attendee['customer_phone'] ?: '-'), 28), 205, $rowY + 8, 7, false, [100, 110, 126]);
            $content .= self::text((string)$attendee['tickets'], 448, $rowY + 15, 10, true, [23, 29, 38], 'center');
            $admitted = (int)$attendee['admitted_tickets'];
            $reserved = (int)$attendee['tickets'];
            $statusColor = $admitted >= $reserved ? [22, 130, 78] : ($admitted > 0 ? [202, 111, 0] : [100, 110, 126]);
            $content .= self::text($admitted . ' / ' . $reserved, 518, $rowY + 15, 9, true, $statusColor, 'center');
        }
        if (empty($attendees)) {
            $content .= self::text('Não existem reservas ativas para este evento.', 297, 463, 10, false, [100, 110, 126], 'center');
        }

        $content .= self::stroke(226, 230, 236) . self::line(42, 61, 553, 61);
        $siteLabel = self::displayUrl($contacts['website']);
        $instagramLabel = '@' . trim(basename(parse_url($contacts['instagram'], PHP_URL_PATH)), '/');
        $content .= self::text($siteLabel, 42, 40, 8, true, [179, 0, 0]);
        $content .= self::text($instagramLabel, 210, 40, 8, true, [179, 0, 0]);
        $content .= self::text($contacts['email'], 355, 40, 8, true, [179, 0, 0]);
        $content .= self::text('Página ' . $page . ' / ' . $pageCount, 553, 40, 8, false, [100, 110, 126], 'right');
        $links[] = ['rect' => [42, 35, 180, 51], 'url' => $contacts['website']];
        $links[] = ['rect' => [210, 35, 335, 51], 'url' => $contacts['instagram']];
        $links[] = ['rect' => [355, 35, 485, 51], 'url' => 'mailto:' . $contacts['email']];
        $content .= "Q\n";

        return ['content' => $content, 'links' => $links];
    }

    private static function assemble(array $pages): string
    {
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            3 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
            4 => '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        ];
        $pageIds = [];
        $nextId = 5;
        foreach ($pages as $page) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $annotationIds = [];
            foreach ($page['links'] as $link) {
                $annotationId = $nextId++;
                $annotationIds[] = $annotationId . ' 0 R';
                $rect = implode(' ', $link['rect']);
                $objects[$annotationId] = '<< /Type /Annot /Subtype /Link /Rect [' . $rect . '] /Border [0 0 0] /A << /S /URI /URI (' . self::escape($link['url']) . ') >> >>';
            }
            $pageIds[] = $pageId . ' 0 R';
            $annots = empty($annotationIds) ? '' : ' /Annots [' . implode(' ', $annotationIds) . ']';
            $objects[$pageId] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents ' . $contentId . ' 0 R' . $annots . ' >>';
            $objects[$contentId] = '<< /Length ' . strlen($page['content']) . ">>\nstream\n" . $page['content'] . "endstream";
        }
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageIds) . '] /Count ' . count($pageIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . ($nextId) . "\n0000000000 65535 f \n";
        for ($i = 1; $i < $nextId; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= 'trailer << /Size ' . $nextId . ' /Root 1 0 R >>' . "\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private static function text(string $value, float $x, float $y, float $size, bool $bold, array $color, string $align = 'left'): string
    {
        $encoded = self::encode($value);
        $estimatedWidth = strlen($encoded) * $size * ($bold ? .56 : .51);
        if ($align === 'right') {
            $x -= $estimatedWidth;
        } elseif ($align === 'center') {
            $x -= $estimatedWidth / 2;
        }
        return self::fill(...$color) . "BT /F" . ($bold ? '2' : '1') . " {$size} Tf {$x} {$y} Td (" . self::escape($encoded) . ") Tj ET\n";
    }

    private static function encode(string $value): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
        return $encoded === false ? '' : $encoded;
    }

    private static function escape(string $value): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $value);
    }

    private static function fill(int $r, int $g, int $b): string
    {
        return sprintf("%.3F %.3F %.3F rg\n", $r / 255, $g / 255, $b / 255);
    }

    private static function stroke(int $r, int $g, int $b): string
    {
        return sprintf("%.3F %.3F %.3F RG\n", $r / 255, $g / 255, $b / 255);
    }

    private static function rect(float $x, float $y, float $width, float $height, bool $fill): string
    {
        return "$x $y $width $height re " . ($fill ? "f\n" : "S\n");
    }

    private static function line(float $x1, float $y1, float $x2, float $y2): string
    {
        return "$x1 $y1 m $x2 $y2 l S\n";
    }

    private static function logoMark(float $x, float $y): string
    {
        return self::fill(11, 18, 32) . self::rect($x, $y, 38, 38, true)
            . self::stroke(47, 60, 93) . self::rect($x, $y, 38, 38, false)
            . self::stroke(242, 185, 64) . "3 w\n"
            . ($x + 7) . ' ' . ($y + 10) . ' m ' . ($x + 13) . ' ' . ($y + 20) . ' ' . ($x + 25) . ' ' . ($y + 20) . ' ' . ($x + 31) . ' ' . ($y + 10) . " c S\n"
            . ($x + 19) . ' ' . ($y + 11) . ' m ' . ($x + 19) . ' ' . ($y + 31) . " l S\n"
            . "1 w\n";
    }

    private static function truncate(string $value, int $length): string
    {
        if (function_exists('mb_strlen') && mb_strlen($value) > $length) {
            return mb_substr($value, 0, $length - 1) . '…';
        }
        return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
    }

    private static function formatDate(string $date): string
    {
        $timestamp = strtotime($date);
        return $timestamp ? date('d/m/Y', $timestamp) : $date;
    }

    private static function displayUrl(string $url): string
    {
        return preg_replace('#^https?://(?:www\.)?#', '', rtrim($url, '/')) ?: $url;
    }
}
