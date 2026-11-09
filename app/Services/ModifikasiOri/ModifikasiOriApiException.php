<?php

namespace App\Services\ModifikasiOri;

use RuntimeException;

/**
 * Error dari API Modifikasi Ori. code = HTTP status (0 = koneksi gagal / timeout).
 */
class ModifikasiOriApiException extends RuntimeException
{
    /** Request mungkin sudah diterima server walau respons tidak sampai (timeout/5xx). */
    public function isUncertain(): bool
    {
        return $this->getCode() === 0 || $this->getCode() >= 500;
    }
}
