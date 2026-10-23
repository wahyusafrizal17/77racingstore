<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mutasi extends Model
{
    protected $table = 'mutasis';

    protected $fillable = [
        'kode',
        'nomor_surat_jalan',
        'tanggal',
        'user_id',
        'warehouse_id',
        'speedshop_id',
        'sumber',
        'destination_type',
        'produksi_tipe',
        'destination_speedshop_id',
        'status',
        'catatan',
        'verified_at',
        'verified_by',
        'inventory_verified_at',
        'inventory_verified_by',
        'rejected_at',
        'rejected_by',
        'rejected_reason',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'verified_at' => 'datetime',
        'inventory_verified_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function inventoryVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'inventory_verified_by');
    }

    public function speedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class);
    }

    public function destinationSpeedshop(): BelongsTo
    {
        return $this->belongsTo(Speedshop::class, 'destination_speedshop_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MutasiItem::class);
    }

    public function isDikirim(): bool
    {
        return $this->status === 'dikirim';
    }

    public function isMenungguManager(): bool
    {
        return $this->status === 'menunggu_manager';
    }

    /**
     * Mutasi dari Produksi ke Warehouse: belum selesai di gudang (belum diterima / ditolak).
     */
    public function isProduksiInboundPending(): bool
    {
        return $this->isFromProduksi()
            && ($this->isDikirim() || $this->isMenungguManager());
    }

    public function isDiterima(): bool
    {
        return $this->status === 'diterima';
    }

    public function isDitolak(): bool
    {
        return $this->status === 'ditolak';
    }

    public function isFromProduksi(): bool
    {
        return ($this->sumber ?? 'produksi') === 'produksi';
    }

    public function isFromWarehouse(): bool
    {
        return ($this->sumber ?? 'produksi') === 'warehouse';
    }

    public function isFromSpeedshop(): bool
    {
        return ($this->sumber ?? '') === 'speedshop';
    }

    public function isToProduksi(): bool
    {
        return ($this->destination_type ?? 'warehouse') === 'produksi';
    }

    /** Mutasi barang dari warehouse ke produksi (stok WH turun, stok produksi naik setelah diterima). */
    public function isWarehouseToProduksi(): bool
    {
        return $this->isFromWarehouse() && $this->isToProduksi();
    }

    public function isWarehouseToProduksiPendingApproval(): bool
    {
        return $this->isWarehouseToProduksi() && $this->isMenungguManager();
    }

    public function isWarehouseToProduksiInTransit(): bool
    {
        return $this->isWarehouseToProduksi() && $this->isDikirim();
    }

    public function produksiTipeLabel(): string
    {
        return \App\Support\Warehouse\ProduksiMutasiTipe::label($this->produksi_tipe);
    }

    public function produksiTipesSummary(): string
    {
        $this->loadMissing('items');

        $labels = $this->items
            ->map(fn (MutasiItem $item) => $item->produksi_tipe ?? $this->produksi_tipe)
            ->filter()
            ->unique()
            ->map(fn (string $tipe) => \App\Support\Warehouse\ProduksiMutasiTipe::label($tipe))
            ->values();

        if ($labels->isNotEmpty()) {
            return $labels->implode(', ');
        }

        return $this->produksiTipeLabel();
    }

    public static function generateWarehouseKode(): string
    {
        $month = now()->format('m');
        $year = now()->year;
        $base = 'MTS/WH/'.$month.'/';
        $like = $base.str_repeat('_', 4);

        $last = static::query()
            ->where('kode', 'like', $like)
            ->whereYear('tanggal', $year)
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        $next = 1;
        if ($last !== null) {
            $tail = substr($last->kode, strlen($base));
            $next = max(1, (int) $tail) + 1;
        }

        return $base.sprintf('%04d', $next);
    }

    public static function generateKode(): string
    {
        $now = Carbon::now();
        $year = $now->year;
        $month = $now->format('m');

        $ssid = auth()->user()?->activeSpeedshopId();
        $suffix = 'UNK';
        if ($ssid) {
            $sp = Speedshop::query()->find($ssid);
            if ($sp) {
                $suffix = $sp->invoiceOutletCode();
            }
        }

        $base = 'MTS/SS/'.$suffix.'/'.$month.'/';
        $like = $base.str_repeat('_', 4);

        $last = static::query()
            ->where('kode', 'like', $like)
            ->whereYear('tanggal', $year)
            ->orderByDesc('id')
            // Kurangi risiko double-generate saat 2 user submit bersamaan (efektif di dalam DB::transaction()).
            ->lockForUpdate()
            ->first();

        $next = 1;
        if ($last !== null) {
            $tail = substr($last->kode, strlen($base));
            $next = max(1, (int) $tail) + 1;
        }

        if ($next > 9999) {
            throw new \RuntimeException('Nomor mutasi untuk cabang ini sudah mencapai batas periode.');
        }

        return $base.sprintf('%04d', $next);
    }

    public static function generateNomorSuratJalan(): string
    {
        $year = now()->format('Y');
        $last = static::where('nomor_surat_jalan', 'like', "SJ-{$year}-%")
            ->orderByRaw('CAST(SUBSTRING(nomor_surat_jalan, 9) AS UNSIGNED) DESC')
            ->value('nomor_surat_jalan');
        $seq = $last ? (int) substr($last, 8) + 1 : 1;

        return "SJ-{$year}-".str_pad($seq, 3, '0', STR_PAD_LEFT);
    }
}
