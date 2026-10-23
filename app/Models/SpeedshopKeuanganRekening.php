<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SpeedshopKeuanganRekening extends Model
{
    public const JENIS_REKENING_UTAMA = 'rekening_utama';

    public const JENIS_KAS_BESAR = 'kas_besar';

    /** Jenis rekening sekunder untuk scope warehouse (label: Rekening Reguler). */
    public const JENIS_REKENING_REGULER = 'rekening_reguler';

    /** Nilai dropdown rekening sumber tunai (bukan baris di tabel rekening). */
    public const SUMBER_TUNAI_VALUE = 'tunai';

    /** Nilai dropdown sumber pemasukan dari luar (bukan rekening internal). */
    public const SUMBER_PIHAK_LAIN_VALUE = 'pihak_lain';

    protected $table = 'speedshop_keuangan_rekenings';

    protected $fillable = [
        'speedshop_id',
        'warehouse_id',
        'owner_user_id',
        'jenis',
        'nama',
        'nama_bank',
        'nomor_rekening',
        'atas_nama',
        'keterangan',
    ];

    /**
     * @return array<string, string>
     */
    public static function jenisOptions(): array
    {
        return [
            self::JENIS_REKENING_UTAMA => 'Rekening Utama',
            self::JENIS_KAS_BESAR => 'Kas Besar',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function jenisOptionsForWarehouse(): array
    {
        return [
            self::JENIS_REKENING_UTAMA => 'Rekening Utama',
            self::JENIS_REKENING_REGULER => 'Rekening Reguler',
        ];
    }

    /**
     * Label kartu ringkasan saldo di halaman Keuangan → Rekening warehouse.
     *
     * @return array<string, string> pocket key => label
     */
    public static function warehouseBalanceCardLabels(): array
    {
        return [
            'rekening_utama' => 'Rekening Utama',
            'cash_besar' => 'Rekening Reguler',
            'laci' => 'Tunai',
        ];
    }

    public static function warehouseJenisLabel(?string $jenis): string
    {
        $jenis = $jenis ?? self::JENIS_REKENING_UTAMA;
        if ($jenis === self::JENIS_KAS_BESAR) {
            return 'Rekening Reguler';
        }

        return self::jenisOptionsForWarehouse()[$jenis]
            ?? self::jenisLabel($jenis);
    }

    /**
     * @return list<string>
     */
    public static function secondaryJenisValues(): array
    {
        return [self::JENIS_KAS_BESAR, self::JENIS_REKENING_REGULER];
    }

    public static function isSecondaryRekeningJenis(?string $jenis): bool
    {
        return in_array($jenis ?? self::JENIS_REKENING_UTAMA, self::secondaryJenisValues(), true);
    }

    public static function jenisLabel(?string $jenis): string
    {
        $jenis = $jenis ?? self::JENIS_REKENING_UTAMA;

        return self::jenisOptionsForWarehouse()[$jenis]
            ?? self::jenisOptions()[$jenis]
            ?? $jenis;
    }

    public function isRekeningUtama(): bool
    {
        return ($this->jenis ?? self::JENIS_REKENING_UTAMA) === self::JENIS_REKENING_UTAMA;
    }

    public function isOwnerRekening(): bool
    {
        return $this->owner_user_id !== null
            && $this->speedshop_id === null
            && $this->warehouse_id === null;
    }

    public function ownerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function financeSetorans(): HasMany
    {
        return $this->hasMany(SpeedshopFinanceSetoran::class, 'speedshop_keuangan_rekening_id');
    }

    public function financeLedgers(): HasMany
    {
        return $this->hasMany(SpeedshopFinanceLedger::class, 'speedshop_keuangan_rekening_id');
    }
}
