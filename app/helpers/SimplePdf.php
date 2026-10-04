<?php

/**
 * Small, dependency-free PDF writer for tabular reports.
 *
 * Text is written with the built-in Helvetica font, so generated reports can
 * be opened without requiring fonts or a PDF package on the server.
 */
class SimplePdf
{
    /** @var array<int, string> */
    private $pages = [];

    public function addPage(array $commands): void
    {
        $this->pages[] = implode("\n", $commands);
    }

    public static function text(float $x, float $y, string $text, int $size = 10, bool $bold = false, array $color = [0.12, 0.12, 0.12]): string
    {
        $encoded = self::escape(self::encode($text));
        return sprintf(
            'BT /%s %d Tf %.3F %.3F %.3F rg %.2F %.2F Td (%s) Tj ET',
            $bold ? 'F2' : 'F1',
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            $encoded
        );
    }

    public static function rectangle(float $x, float $y, float $width, float $height, array $color): string
    {
        return sprintf('%.3F %.3F %.3F rg %.2F %.2F %.2F %.2F re f', $color[0], $color[1], $color[2], $x, $y, $width, $height);
    }

    public static function truncate(string $text, int $length): string
    {
        if (function_exists('mb_strlen') && mb_strlen($text, 'UTF-8') > $length) {
            return mb_substr($text, 0, max(1, $length - 1), 'UTF-8') . '…';
        }
        return strlen($text) > $length ? substr($text, 0, max(1, $length - 3)) . '...' : $text;
    }

    public function render(): string
    {
        if (!$this->pages) {
            $this->addPage([]);
        }

        $objects = [];
        $pageCount = count($this->pages);
        $fontRegularId = 3 + ($pageCount * 2);
        $fontBoldId = $fontRegularId + 1;

        $kids = [];
        foreach ($this->pages as $index => $content) {
            $pageId = 3 + ($index * 2);
            $contentId = $pageId + 1;
            $kids[] = $pageId . ' 0 R';
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595.28 841.89] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>',
                $fontRegularId,
                $fontBoldId,
                $contentId
            );
            $objects[$contentId] = '<< /Length ' . strlen($content) . ">>\nstream\n" . $content . "\nendstream";
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';
        $objects[$fontRegularId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objects[$fontBoldId] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $body . "\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
        for ($id = 1; $id <= count($objects); $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id]);
        }
        $pdf .= 'trailer << /Size ' . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n" . $xref . "\n%%EOF";
        return $pdf;
    }

    private static function encode(string $text): string
    {
        $encoded = iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
        return $encoded === false ? preg_replace('/[^\x20-\x7E]/', '?', $text) : $encoded;
    }

    private static function escape(string $text): string
    {
        return str_replace(['\\', '(', ')', "\r", "\n"], ['\\\\', '\\(', '\\)', '', ' '], $text);
    }
}
