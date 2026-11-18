<?php

namespace App\Support;

/**
 * A tiny one-page PDF writer used for certificates.
 *
 * It uses the PDF built-in fonts (Helvetica), so no Composer package and no
 * font files are needed. Limitation: only characters in the Western/Latin
 * "Windows-1252" set can be printed; anything else (for example Bengali
 * letters) is shown as "?".
 *
 * Coordinates are in PDF points (1/72 inch) with (0, 0) at the BOTTOM-left.
 */
class SimplePdf
{
    public const PAGE_WIDTH = 842;   // A4 landscape

    public const PAGE_HEIGHT = 595;

    /** Fonts available to text(): key => [PDF resource name, PDF base font, width table name]. */
    private const FONTS = [
        'regular' => ['F1', 'Helvetica', 'regular'],
        'bold' => ['F2', 'Helvetica-Bold', 'bold'],
        'italic' => ['F3', 'Helvetica-Oblique', 'regular'], // Oblique has the same widths as regular.
    ];

    /** Character widths (1000 units per em) for ASCII 32..126. */
    private const WIDTHS_REGULAR = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584,
        278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556,
        556, 556, 278, 278, 584, 584, 584, 556, 1015, 667, 667, 722,
        722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278,
        278, 278, 469, 556, 333, 556, 556, 500, 556, 556, 278, 556,
        556, 222, 222, 500, 222, 833, 556, 556, 556, 556, 333, 500,
        278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    private const WIDTHS_BOLD = [
        278, 333, 474, 556, 556, 889, 722, 238, 333, 333, 389, 584,
        278, 333, 278, 278, 556, 556, 556, 556, 556, 556, 556, 556,
        556, 556, 333, 333, 584, 584, 584, 611, 975, 667, 667, 722,
        722, 667, 611, 778, 722, 278, 556, 722, 611, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 333,
        278, 333, 584, 556, 333, 556, 611, 556, 611, 556, 333, 611,
        611, 278, 278, 556, 222, 889, 611, 611, 611, 611, 389, 556,
        333, 611, 556, 778, 556, 556, 500, 389, 280, 389, 584,
    ];

    private string $content = '';

    /**
     * Print one line of text, horizontally centred on the page.
     * If the text is too wide it is shrunk (down to $minSize) and then cut with "...".
     *
     * @param  array{0: float, 1: float, 2: float}  $rgb  colour, each 0..1
     */
    public function centeredText(string $text, float $y, float $size, string $font = 'regular', array $rgb = [0, 0, 0], float $maxWidth = 700, float $minSize = 10): static
    {
        $bytes = $this->encode($text);

        while ($this->widthOf($bytes, $font, $size) > $maxWidth && $size > $minSize) {
            $size -= 1;
        }

        while ($this->widthOf($bytes, $font, $size) > $maxWidth && strlen($bytes) > 4) {
            $bytes = rtrim(substr($bytes, 0, -4)).'...';
        }

        $x = (self::PAGE_WIDTH - $this->widthOf($bytes, $font, $size)) / 2;

        $this->content .= "BT\n"
            .'/'.self::FONTS[$font][0].' '.$this->num($size)." Tf\n"
            .$this->num($rgb[0]).' '.$this->num($rgb[1]).' '.$this->num($rgb[2])." rg\n"
            .$this->num($x).' '.$this->num($y)." Td\n"
            .'('.$this->escape($bytes).") Tj\n"
            ."ET\n";

        return $this;
    }

    /**
     * Draw a rectangle outline.
     *
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    public function rect(float $x, float $y, float $width, float $height, float $lineWidth = 1, array $rgb = [0, 0, 0]): static
    {
        $this->content .= $this->num($lineWidth)." w\n"
            .$this->num($rgb[0]).' '.$this->num($rgb[1]).' '.$this->num($rgb[2])." RG\n"
            .$this->num($x).' '.$this->num($y).' '.$this->num($width).' '.$this->num($height)." re\nS\n";

        return $this;
    }

    /**
     * Draw a straight line.
     *
     * @param  array{0: float, 1: float, 2: float}  $rgb
     */
    public function line(float $x1, float $y1, float $x2, float $y2, float $lineWidth = 1, array $rgb = [0, 0, 0]): static
    {
        $this->content .= $this->num($lineWidth)." w\n"
            .$this->num($rgb[0]).' '.$this->num($rgb[1]).' '.$this->num($rgb[2])." RG\n"
            .$this->num($x1).' '.$this->num($y1)." m\n"
            .$this->num($x2).' '.$this->num($y2)." l\nS\n";

        return $this;
    }

    /** Build the finished PDF file as a binary string. */
    public function output(): string
    {
        $fontRefs = '';
        $objects = [
            1 => '<< /Type /Catalog /Pages 2 0 R >>',
            2 => '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        ];

        $number = 5;
        foreach (self::FONTS as $font) {
            $fontRefs .= '/'.$font[0].' '.$number.' 0 R ';
            $objects[$number] = '<< /Type /Font /Subtype /Type1 /BaseFont /'.$font[1].' /Encoding /WinAnsiEncoding >>';
            $number++;
        }

        $objects[3] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '.self::PAGE_WIDTH.' '.self::PAGE_HEIGHT.'] '
            .'/Resources << /Font << '.$fontRefs.'>> >> /Contents 4 0 R >>';
        $objects[4] = '<< /Length '.strlen($this->content).' >>'."\nstream\n".$this->content."\nendstream";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id." 0 obj\n".$body."\nendobj\n";
        }

        $size = count($objects) + 1;
        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 ".$size."\n";
        $pdf .= "0000000000 65535 f \n";
        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size ".$size." /Root 1 0 R >>\nstartxref\n".$xrefStart."\n%%EOF\n";

        return $pdf;
    }

    /** UTF-8 text to single-byte Windows-1252 (unknown characters become "?"). */
    private function encode(string $text): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $text) ?? '';
        $converted = mb_convert_encoding($text, 'Windows-1252', 'UTF-8');

        return trim($converted);
    }

    private function widthOf(string $bytes, string $font, float $size): float
    {
        $table = self::FONTS[$font][2] === 'bold' ? self::WIDTHS_BOLD : self::WIDTHS_REGULAR;
        $total = 0;

        foreach (str_split($bytes) as $char) {
            $code = ord($char);
            $total += ($code >= 32 && $code <= 126) ? $table[$code - 32] : 556;
        }

        return $total * $size / 1000;
    }

    private function escape(string $bytes): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $bytes);
    }

    /** Locale-independent number formatting for PDF operators. */
    private function num(float|int $value): string
    {
        $text = number_format((float) $value, 3, '.', '');

        return rtrim(rtrim($text, '0'), '.') ?: '0';
    }
}
