<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ServiceOrder extends Model
{
    use HasFactory;

    public const TIPE_SERVIS = 'servis';

    public const TIPE_PEMBELIAN_PART = 'pembelian_part';

    protected $fillable = [
        'warehouse_id', 'speedshop_id', 'tipe_pemesanan', 'kode_servis', 'pelanggan_id', 'kendaraan_id', 'mekanik_id',
        'keluhan', 'sumber_kedatangan', 'kategori_service', 'jenis_pembayaran', 'pembayaran_split', 'estimasi_biaya', 'status',
        'tanggal_masuk', 'tanggal_selesai', 'kilometer', 'next_service_date',
        'diskon_verifikasi_status',
        'diskon_verifikasi_requested_by', 'diskon_verifikasi_requested_at',
        'diskon_verifikasi_verified_by', 'diskon_verifikasi_verified_at',
        'diskon_verifikasi_rejected_by', 'diskon_verifikasi_rejected_at',
        'diskon_verifikasi_rejected_reason',
        'batal_verifikasi_status',
        'batal_verifikasi_requested_by', 'batal_verifikasi_requested_at', 'batal_verifikasi_reason',
        'batal_verifikasi_verified_by', 'batal_verifikasi_verified_at',
        'batal_verifikasi_rejected_by', 'batal_verifikasi_rejected_at', 'batal_verifikasi_rejected_reason',
    ];

    public const SUMBER_KEDATANGAN = [
        'relasi_teman' => 'Relasi/Teman',
        'whatsapp' => 'Whatsapp',
        'facebook' => 'Facebook',
        'ig' => 'IG',
        'tiktok' => 'Tiktok',
        'marketplace' => 'Marketplace',
        'datang_langsung' => 'Datang Langsung',
        'dll' => 'DLL',
    ];

    public const KATEGORI_SERVICE = [
        'reguler' => 'Reguler',
        'booking' => 'Booking',
        'express' => 'Express',
        'job_return' => 'Job return',
    ];

    /**
     * Label jenis order untuk dashboard / export (bukan enum DB kategori_service).
     * Pastikan relasi jasaServis & products sudah di-load bila memungkinkan.
     */
    public function kategoriServiceDisplay(): string
    {
        $k = $this->kategori_service;
        if ($k === null || $k === '') {
            return '-';
        }

        return self::KATEGORI_SERVICE[$k] ?? (string) $k;
    }

    public function jenisOrderDisplay(): string
    {
        if (($this->tipe_pemesanan ?? self::TIPE_SERVIS) === self::TIPE_PEMBELIAN_PART) {
            return 'Pembelian Part';
        }

        $hasJasa = $this->jasaServis->isNotEmpty();
        $hasParts = $this->products->isNotEmpty();

        if ($hasJasa && $hasParts) {
            return 'Servis & Penggantian Parts';
        }
        if ($hasJasa) {
            return 'Servis';
        }
        if ($hasParts) {
            return 'Penggantian Parts';
        }

        return '-';
    }

    /**
     * Label metode pembayaran untuk daftar history / UI (termasuk split bill dari JSON).
     */
    public function pembayaranDisplayLabel(): string
    {
        $this->loadMissing('invoice');

        return Invoice::formatMetodePembayaranLabel(
            $this->invoice?->metode_pembayaran ?? $this->jenis_pembayaran,
            $this->invoice?->pembayaran_split ?? $this->pembayaran_split,
        );
    }

    /**
     * Total final transaksi untuk tampilan/laporan.
     * Prioritas invoice.grand_total (sudah include charge + PPN), fallback ke grand_total service order.
     */
    public function finalGrandTotal(): int
    {
        if ($this->relationLoaded('invoice')) {
            return (int) ($this->invoice?->grand_total ?? $this->grand_total ?? 0);
        }

        return (int) ($this->invoice()->value('grand_total') ?? $this->grand_total ?? 0);
    }

    /**
     * @return array{pendapatan_omset: int, charge: int, ppn: int, total_tagihan: int}
     */
    public function invoiceOmsetBreakdown(): array
    {
        $this->loadMissing('invoice');
        $totalTagihan = $this->finalGrandTotal();
        $ppn = (int) ($this->invoice?->ppn_amount ?? 0);
        $charge = (int) ($this->invoice?->charge_amount ?? 0);

        if ($charge <= 0 && $this->invoice) {
            $charge = \App\Services\InvoiceSettingsService::resolveChargeAmountForInvoice(
                $this->invoice,
                (int) ($this->grand_total ?? 0),
            );
        }

        return [
            'pendapatan_omset' => max(0, $totalTagihan - $charge - $ppn),
            'charge' => $charge,
            'ppn' => $ppn,
            'total_tagihan' => $totalTagihan,
        ];
    }

    protected $casts = [
        'tanggal_masuk' => 'date',
        'tanggal_selesai' => 'date',
        'next_service_date' => 'date',
        'diskon_verifikasi_requested_at' => 'datetime',
        'diskon_verifikasi_verified_at' => 'datetime',
        'diskon_verifikasi_rejected_at' => 'datetime',
        'batal_verifikasi_requested_at' => 'datetime',
        'batal_verifikasi_verified_at' => 'datetime',
        'batal_verifikasi_rejected_at' => 'datetime',
        'pembayaran_split' => 'array',
    ];

    /**
     * Nomor work order speedshop: WO/SS/{KTP|CLK|CSK|UNK}/{MM}/#### (4 digit, reset per bulan per cabang, berdasarkan tanggal masuk).
     */
    public static function generateNextKode(int $pelangganId, Carbon $tanggalMasuk, ?int $speedshopId = null): string
    {
        $branch = Speedshop::resolveOutletSuffixFromPelangganId($pelangganId);
        $tanggal = Carbon::parse($tanggalMasuk);
        $bulan = $tanggal->format('m');
        // Nomor urut dipisah per speedshop/outlet (bukan global lintas outlet).
        $lockKey = 'wo-kode-gen:'.strtoupper($branch).':'.($speedshopId ? ('ssid:'.$speedshopId) : 'ssid:0').':'.$tanggal->format('Y-m');

        return Cache::lock($lockKey, 15)->block(8, function () use ($tanggal, $branch, $bulan, $speedshopId) {
            return DB::transaction(function () use ($tanggal, $branch, $bulan, $speedshopId) {
                $base = 'WO/SS/'.$branch.'/'.$bulan.'/';
                $like = $base.str_repeat('_', 4);
                $monthStart = $tanggal->copy()->startOfMonth()->toDateString();
                $monthEnd = $tanggal->copy()->endOfMonth()->toDateString();

                $lastQ = static::query()
                    ->where('kode_servis', 'like', $like)
                    ->whereBetween('tanggal_masuk', [$monthStart, $monthEnd])
                    ->when(
                        $speedshopId && Schema::hasColumn('service_orders', 'speedshop_id'),
                        fn ($q) => $q->where('speedshop_id', (int) $speedshopId)
                    );

                $last = $lastQ
                    ->orderByDesc('id')
                    ->lockForUpdate()
                    ->first();

                $next = 1;
                if ($last !== null) {
                    $suffix = substr($last->kode_servis, strlen($base));
                    $next = max(1, (int) $suffix) + 1;
                }

                if ($next > 9999) {
                    throw new \RuntimeException('Nomor work order untuk cabang ini sudah mencapai batas periode.');
                }

                return $base.sprintf('%04d', $next);
            });
        });
    }

    /**
     * Kilometer dari work order terbaru untuk kendaraan (urut berdasarkan tanggal selesai bila ada, lalu tanggal masuk, lalu id).
     */
    public static function latestKilometerForKendaraan(int $kendaraanId): ?int
    {
        $map = static::latestKilometersForKendaraanIds([$kendaraanId]);

        return $map[$kendaraanId] ?? null;
    }

    /**
     * KM terakhir per kendaraan (satu query), urutan sama latestKilometerForKendaraan.
     *
     * @param  list<int>  $kendaraanIds
     * @return array<int, int>
     */
    public static function latestKilometersForKendaraanIds(array $kendaraanIds): array
    {
        $ids = array_values(array_unique(array_filter(
            array_map('intval', $kendaraanIds),
            fn (int $id) => $id > 0,
        )));
        if ($ids === []) {
            return [];
        }

        $ranked = static::query()
            ->selectRaw('kendaraan_id, kilometer, ROW_NUMBER() OVER (PARTITION BY kendaraan_id ORDER BY COALESCE(tanggal_selesai, tanggal_masuk) DESC, id DESC) as rn')
            ->whereIn('kendaraan_id', $ids)
            ->whereNotNull('kilometer')
            ->toBase();

        return DB::query()
            ->fromSub($ranked, 'so_km')
            ->where('rn', 1)
            ->pluck('kilometer', 'kendaraan_id')
            ->map(fn ($km) => (int) $km)
            ->all();
    }

    /**
     * Tanggal transaksi / penyelesaian untuk laporan & dashboard (bukan tanggal WO dibuat).
     */
    public function transaksiTanggal(): Carbon
    {
        if ($this->tanggal_selesai) {
            return Carbon::parse($this->tanggal_selesai);
        }

        if ($this->relationLoaded('invoice') && $this->invoice?->tanggal) {
            return Carbon::parse($this->invoice->tanggal);
        }

        return Carbon::parse($this->tanggal_masuk);
    }

    /**
     * Filter WO selesai menurut tanggal penyelesaian transaksi (bukan tanggal masuk antrian).
     */
    public function scopeWhereTransaksiDateBetween(Builder $query, string $startYmd, string $endYmd): Builder
    {
        return $query->whereBetween(
            DB::raw(static::transaksiDateSql()),
            [$startYmd, $endYmd],
        );
    }

    protected static function booted(): void
    {
        static::updated(function (ServiceOrder $order) {
            if ($order->wasChanged('status') && $order->status === 'selesai') {
                $order->snapshotProductHpp();
            }
        });
    }

    /**
     * Kunci HPP sparepart saat WO selesai agar laporan tidak berubah ketika HPP master naik.
     */
    public function snapshotProductHpp(): void
    {
        DB::table('service_order_products')
            ->join('products', 'service_order_products.product_id', '=', 'products.id')
            ->where('service_order_products.service_order_id', $this->id)
            ->whereNull('service_order_products.hpp')
            ->update([
                'service_order_products.hpp' => DB::raw('COALESCE(NULLIF(products.hpp, 0), products.harga_pembelian, 0)'),
            ]);
    }

    public static function transaksiDateSql(string $table = 'service_orders'): string
    {
        return "DATE(COALESCE({$table}.tanggal_selesai, {$table}.tanggal_masuk))";
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function speedshop()
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function pelanggan()
    {
        return $this->belongsTo(Pelanggan::class);
    }

    public function kendaraan()
    {
        return $this->belongsTo(Kendaraan::class);
    }

    public function mekanik()
    {
        return $this->belongsTo(Mekanik::class);
    }

    public function batalVerifikasiRequestedBy()
    {
        return $this->belongsTo(User::class, 'batal_verifikasi_requested_by');
    }

    public function diskonVerifikasiRequestedBy()
    {
        return $this->belongsTo(User::class, 'diskon_verifikasi_requested_by');
    }

    public function diskonVerifikasiVerifiedBy()
    {
        return $this->belongsTo(User::class, 'diskon_verifikasi_verified_by');
    }

    public function diskonVerifikasiRejectedBy()
    {
        return $this->belongsTo(User::class, 'diskon_verifikasi_rejected_by');
    }

    public function batalVerifikasiVerifiedBy()
    {
        return $this->belongsTo(User::class, 'batal_verifikasi_verified_by');
    }

    public function batalVerifikasiRejectedBy()
    {
        return $this->belongsTo(User::class, 'batal_verifikasi_rejected_by');
    }

    public function jasaServis()
    {
        return $this->belongsToMany(JasaServis::class, 'service_order_jasa')
            ->withPivot('qty', 'biaya', 'diskon', 'catatan_diskon', 'diskon_verified_by', 'diskon_verified_at')->withTimestamps();
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'service_order_products')
            ->withPivot('qty', 'harga', 'hpp', 'diskon', 'catatan_diskon', 'diskon_verified_by', 'diskon_verified_at')->withTimestamps();
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function getTotalJasaAttribute(): int
    {
        return $this->jasaServis->sum(fn ($j) => ((int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya) - (int) ($j->pivot->diskon ?? 0));
    }

    public function getTotalSparepartAttribute(): int
    {
        return $this->products->sum(fn ($p) => ($p->pivot->qty * $p->pivot->harga) - (int) ($p->pivot->diskon ?? 0));
    }

    public function getGrandTotalAttribute(): int
    {
        return $this->total_jasa + $this->total_sparepart;
    }

    public function getTotalDiskonAttribute(): int
    {
        $jasa = $this->jasaServis->sum(fn ($j) => (int) ($j->pivot->diskon ?? 0));
        $sparepart = $this->products->sum(fn ($p) => (int) ($p->pivot->diskon ?? 0));

        return $jasa + $sparepart;
    }

    public function getSubtotalSebelumDiskonAttribute(): int
    {
        $jasa = $this->jasaServis->sum(fn ($j) => (int) ($j->pivot->qty ?? 1) * (int) $j->pivot->biaya);
        $sparepart = $this->products->sum(fn ($p) => $p->pivot->qty * $p->pivot->harga);

        return $jasa + $sparepart;
    }

    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'antri' => 'bg-yellow-100 text-yellow-700',
            'proses' => 'bg-blue-100 text-blue-700',
            'selesai' => 'bg-green-100 text-green-700',
            'dibatalkan' => 'bg-red-100 text-red-700',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function getDiskonVerifikasiBadgeAttribute(): ?string
    {
        return match ($this->diskon_verifikasi_status) {
            'pending' => 'bg-amber-100 text-amber-800',
            'approved' => 'bg-emerald-100 text-emerald-800',
            'rejected' => 'bg-red-100 text-red-700',
            default => null,
        };
    }
}
