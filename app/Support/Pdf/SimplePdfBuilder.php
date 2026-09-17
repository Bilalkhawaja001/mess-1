<?php

namespace App\Support\Pdf;

trait SimplePdfBuilder
{
    protected function assemblePdf(
        array $pageStreams,
        bool $hasLogo,
        ?string $logoData,
        int $logoWidth,
        int $logoHeight,
        int $pageW = 842,
        int $pageH = 595
    ): string {
        /*
         * PDF OBJECTS
         */

        $n = count($pageStreams);

        $regularFontId =
            3 + ($n * 2);

        $boldFontId =
            $regularFontId + 1;

        $imageObjectId =
            $hasLogo
                ? $boldFontId + 1
                : null;

        $objects = [];

        $objects[1] =
            '<< /Type /Catalog /Pages 2 0 R >>';

        $kids = [];

        foreach ($pageStreams as $i => $stream) {
            $pageId =
                3 + ($i * 2);

            $contentId =
                $pageId + 1;

            $kids[] =
                $pageId.' 0 R';

            $xObject = $hasLogo
                ? ' /XObject << /Im1 '.$imageObjectId.' 0 R >>'
                : '';

            $objects[$pageId] =
                '<< /Type /Page'
                .' /Parent 2 0 R'
                .' /MediaBox [0 0 '.$pageW.' '.$pageH.']'
                .' /Resources <<'
                .' /Font <<'
                .' /F1 '.$regularFontId.' 0 R'
                .' /F2 '.$boldFontId.' 0 R'
                .' >>'
                .$xObject
                .' >>'
                .' /Contents '.$contentId.' 0 R'
                .' >>';

            $objects[$contentId] =
                '<< /Length '.strlen($stream)." >>\n"
                ."stream\n"
                .$stream
                ."\nendstream";
        }

        $objects[2] =
            '<< /Type /Pages /Kids ['
            .implode(' ', $kids)
            .'] /Count '.$n
            .' >>';

        $objects[$regularFontId] =
            '<< /Type /Font'
            .' /Subtype /Type1'
            .' /BaseFont /Helvetica'
            .' >>';

        $objects[$boldFontId] =
            '<< /Type /Font'
            .' /Subtype /Type1'
            .' /BaseFont /Helvetica-Bold'
            .' >>';

        if ($hasLogo && $imageObjectId !== null) {
            $objects[$imageObjectId] =
                '<< /Type /XObject'
                .' /Subtype /Image'
                .' /Width '.$logoWidth
                .' /Height '.$logoHeight
                .' /ColorSpace /DeviceRGB'
                .' /BitsPerComponent 8'
                .' /Filter /DCTDecode'
                .' /Length '.strlen($logoData)
                ." >>\n"
                ."stream\n"
                .$logoData
                ."\nendstream";
        }

        ksort($objects);

        $lastObjectId =
            $hasLogo && $imageObjectId !== null
                ? $imageObjectId
                : $boldFontId;

        $pdf = "%PDF-1.4\n";

        $offsets = [
            0 => 0,
        ];

        for ($id = 1; $id <= $lastObjectId; $id++) {
            $offsets[$id] =
                strlen($pdf);

            $pdf .=
                $id." 0 obj\n"
                .$objects[$id]
                ."\nendobj\n";
        }

        $xrefPosition =
            strlen($pdf);

        $pdf .=
            "xref\n"
            ."0 ".($lastObjectId + 1)."\n"
            ."0000000000 65535 f \n";

        for ($id = 1; $id <= $lastObjectId; $id++) {
            $pdf .= sprintf(
                "%010d 00000 n \n",
                $offsets[$id]
            );
        }

        $pdf .=
            "trailer\n"
            .'<< /Size '.($lastObjectId + 1)
            .' /Root 1 0 R >>'
            ."\n"
            ."startxref\n"
            .$xrefPosition
            ."\n%%EOF";

        return $pdf;
    }

    protected function pdfText(
        float $x,
        float $y,
        string $text,
        float $size = 8,
        bool $bold = false
    ): string {
        $font =
            $bold
                ? 'F2'
                : 'F1';

        return
            "0 0 0 rg BT /{$font} {$size} Tf "
            ."{$x} {$y} Td ("
            .$this->escapePdfText($text)
            .") Tj ET\n";
    }

    protected function pdfCenterText(float $pageWidth, float $y, string $text, float $size = 8, bool $bold = false): string
    {
        $width = strlen($text) * $size * 0.50;

        return $this->pdfText(max(0, ($pageWidth - $width) / 2), $y, $text, $size, $bold);
    }

    protected function pdfRightText(
        float $rightX,
        float $y,
        string $text,
        float $size = 8,
        bool $bold = false
    ): string {
        $width =
            strlen($text)
            * $size
            * 0.50;

        return $this->pdfText(
            max(0, $rightX - $width),
            $y,
            $text,
            $size,
            $bold
        );
    }

    protected function pdfLine(
        float $x1,
        float $y1,
        float $x2,
        float $y2,
        float $width = 0.6
    ): string {
        return
            "0 0 0 RG {$width} w "
            ."{$x1} {$y1} m "
            ."{$x2} {$y2} l S\n";
    }

    protected function pdfFillRect(
        float $x,
        float $y,
        float $w,
        float $h,
        float $r,
        float $g,
        float $b
    ): string {
        return
            "{$r} {$g} {$b} rg "
            ."{$x} {$y} "
            ."{$w} {$h} re f\n";
    }

    protected function escapePdfText(string $text): string
    {
        $text = str_replace(
            ["\r", "\n", "\t", '—', '–'],
            [' ', ' ', ' ', '-', '-'],
            $text
        );

        $text = preg_replace(
            '/[^\x20-\x7E]/',
            '?',
            $text
        ) ?? '';

        return str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            $text
        );
    }
}
