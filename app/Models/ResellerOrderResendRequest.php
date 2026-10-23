<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResellerOrderResendRequest extends Model
{
    public const STATUS_MENUNGGU = 'menunggu';

    public const STATUS_DIPROSES = 'diproses';

    public const STATUS_SELESAI = 'selesai';

    public const STATUS_DITOLAK = 'ditolak';

    public const STATUS_DIBATALKAN = 'dibatalkan';

    protected $fillable = [
        'reseller_order_id',
        'warehouse_id',
        'requested_by',
        'status',
        'catatan',
        'nama_penerima',
        'no_hp_penerima',
        'alamat_penerima',
        'bukti_path',
        'bukti_paths',
        'no_resi',
        'bukti_resi_path',
        'processed_by',
        'processed_at',
        'packing_scanned_at',
        'dikirim_at',
        'penolakan_alasan',
    ];

    protected function casts(): array
    {
        return [
            'bukti_paths' => 'array',
            'processed_at' => 'datetime',
            'packing_scanned_at' => 'datetime',
            'dikirim_at' => 'datetime',
        ];
    }

    /** @return array<string, string> */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_MENUNGGU => 'Menunggu gudang',
            self::STATUS_DIPROSES => 'Diproses',
            self::STATUS_SELESAI => 'Selesai dikirim',
            self::STATUS_DITOLAK => 'Ditolak',
            self::STATUS_DIBATALKAN => 'Dibatalkan',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_MENUNGGU, self::STATUS_DIPROSES], true);
    }

    public static function pendingWarehouseCount(?int $warehouseId): int
    {
        if ($warehouseId === null || $warehouseId < 1) {
            return 0;
        }

        return static::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('status', [self::STATUS_MENUNGGU, self::STATUS_DIPROSES])
            ->count();
    }

    public static function pendingCountForReseller(?int $resellerId): int
    {
        static $memo = [];
        $resellerId = (int) $resellerId;
        if ($resellerId < 1) {
            return 0;
        }

        return $memo[$resellerId] ??= static::query()
            ->whereHas('order', fn ($q) => $q->where('warehouse_reseller_id', $resellerId))
            ->whereIn('status', [self::STATUS_MENUNGGU, self::STATUS_DIPROSES])
            ->count();
    }

    public static function pendingCountForMoOperator(?int $userId): int
    {
        static $memo = [];
        $userId = (int) $userId;
        if ($userId < 1) {
            return 0;
        }

        return $memo[$userId] ??= static::query()
            ->whereHas('order', fn ($q) => $q->where('user_id', $userId)->where('order_source', ResellerOrder::SOURCE_MO_OPERATOR))
            ->whereIn('status', [self::STATUS_MENUNGGU, self::STATUS_DIPROSES])
            ->count();
    }

    public function canCancelByRequester(): bool
    {
        return $this->status === self::STATUS_MENUNGGU;
    }

    public function hasWarehouseResi(): bool
    {
        return trim((string) $this->no_resi) !== '' && filled($this->bukti_resi_path);
    }

    public function canWarehouseScan(): bool
    {
        return $this->status === self::STATUS_DIPROSES
            && $this->hasWarehouseResi();
    }

    public function canWarehouseResetScan(): bool
    {
        return $this->status === self::STATUS_DIPROSES
            && $this->hasWarehouseResi();
    }

    public function isPackingScanComplete(): bool
    {
        $this->loadMissing('items');

        if ($this->items->isEmpty()) {
            return false;
        }

        return $this->items->every(fn (ResellerOrderResendRequestItem $item) => $item->isScanComplete());
    }

    /** @return array{needed: int, scanned: int, complete: bool} */
    public function packingScanProgress(): array
    {
        $this->loadMissing('items');
        $needed = (int) $this->items->sum('qty_kirim_ulang');
        $scanned = (int) $this->items->sum('qty_scanned');

        return [
            'needed' => $needed,
            'scanned' => min($scanned, $needed),
            'complete' => $needed > 0 && $scanned >= $needed,
        ];
    }

    public function canWarehouseShip(): bool
    {
        return $this->status === self::STATUS_DIPROSES
            && $this->hasWarehouseResi()
            && $this->isPackingScanComplete();
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ResellerOrder::class, 'reseller_order_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /** @return HasMany<ResellerOrderResendRequestItem> */
    public function items(): HasMany
    {
        return $this->hasMany(ResellerOrderResendRequestItem::class, 'reseller_order_resend_request_id');
    }

    public function buktiUrl(): ?string
    {
        $paths = $this->buktiPathsList();
        $path = $paths[0] ?? $this->bukti_path;
        if (! $path) {
            return null;
        }

        return \App\Support\UploadStorage::url($path);
    }

    /**
     * @return list<string>
     */
    public function buktiPathsList(): array
    {
        $paths = $this->bukti_paths;
        if (is_array($paths) && $paths !== []) {
            return array_values(array_filter(array_map(
                static fn ($path) => is_string($path) ? trim($path) : '',
                $paths
            )));
        }

        if (is_string($this->bukti_path) && trim($this->bukti_path) !== '') {
            return [trim($this->bukti_path)];
        }

        return [];
    }

    /**
     * @return list<array{path: string, url: string, type: string, label: string}>
     */
    public function buktiFiles(): array
    {
        $out = [];
        foreach ($this->buktiPathsList() as $index => $path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            $type = in_array($ext, ['mp4', 'mov', 'webm', '3gp', 'm4v'], true) ? 'video' : 'image';
            $out[] = [
                'path' => $path,
                'url' => \App\Support\UploadStorage::url($path),
                'type' => $type,
                'label' => ($type === 'video' ? 'Video' : 'Foto').' '.($index + 1),
            ];
        }

        return $out;
    }

    public function buktiResiUrl(): ?string
    {
        if (! $this->bukti_resi_path) {
            return null;
        }

        return \App\Support\UploadStorage::url($this->bukti_resi_path);
    }
}
