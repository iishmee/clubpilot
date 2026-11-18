<?php

namespace Tests\Unit;

use App\Support\SimplePdf;
use PHPUnit\Framework\TestCase;

class SimplePdfTest extends TestCase
{
    public function test_output_is_a_well_formed_pdf_with_correct_offsets(): void
    {
        $pdf = (new SimplePdf)
            ->rect(10, 10, 100, 100, 2, [0, 0, 1])
            ->line(10, 10, 100, 100)
            ->centeredText('Hello certificate', 300, 20, 'bold')
            ->output();

        $this->assertStringStartsWith('%PDF-1.4', $pdf);
        $this->assertStringEndsWith("%%EOF\n", $pdf);

        // The offset after "startxref" must point exactly at the "xref" keyword.
        preg_match('/startxref\n(\d+)\n%%EOF/', $pdf, $match);
        $this->assertSame('xref', substr($pdf, (int) $match[1], 4));

        // Every object offset in the table must point at "<number> 0 obj".
        preg_match_all('/(\d{10}) 00000 n /', $pdf, $offsets);
        $this->assertCount(7, $offsets[1]);
        foreach ($offsets[1] as $index => $offset) {
            $this->assertStringStartsWith(($index + 1).' 0 obj', substr($pdf, (int) $offset, 12));
        }
    }

    public function test_special_characters_are_escaped_and_accents_are_kept(): void
    {
        $pdf = (new SimplePdf)
            ->centeredText('Jos'."\u{00E9}".' (A) \\ B', 300, 20)
            ->output();

        // "é" becomes the single Windows-1252 byte E9; ( ) and \ are escaped.
        $this->assertStringContainsString('(Jos'."\xE9".' \\(A\\) \\\\ B) Tj', $pdf);
    }

    public function test_characters_outside_latin_are_replaced_instead_of_breaking_the_file(): void
    {
        $pdf = (new SimplePdf)->centeredText("\u{09B0}\u{09B9}\u{09BF}\u{09AE} Rahim", 300, 20)->output();

        $this->assertStringContainsString('Rahim) Tj', $pdf);
        $this->assertStringContainsString('%%EOF', $pdf);
    }
}
