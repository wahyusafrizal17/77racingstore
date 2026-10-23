<?php

namespace App\Support;

class PdfFilename
{
    /**
     * Strip characters invalid in Content-Disposition filenames (e.g. / in mutasi kode).
     */
    public static function safeSegment(string $value): string
    {
        $safe = preg_replace('/[^\w\-]+/u', '_', $value);

        return $safe !== '' ? $safe : 'file';
    }

    public static function mutasi(string $kode): string
    {
        return 'Mutasi-'.self::safeSegment($kode).'-'.now()->format('Y-m-d').'.pdf';
    }
}
