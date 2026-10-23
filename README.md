# 77racing

Aplikasi operasional 77racing (Speedshop, Warehouse, Produksi, Reseller, Admin).

## Deploy ke server

### 1. Install dependency

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
```

### 2. Environment

```bash
cp .env.example .env
php artisan key:generate
# isi DB_*, MIDTRANS_*, dll. di .env
```

### 3. Migration (urutan otomatis)

**Backup database dulu** sebelum migrate di production:

```bash
# contoh MySQL
mysqldump -u USER -p NAMA_DB > backup-$(date +%F-%H%M).sql
```

Jalankan **dua perintah** ini setelah deploy — aman di production:

```bash
php artisan migrate --force
php artisan db:seed --force
```

> **`migrate`** — schema + backfill user/role (lihat tabel di bawah). Tidak menghapus transaksi.
>
> **`db:seed`** — hanya data referensi (kota, gudang, cabang, master akun operasional). **Tidak** menyentuh transaksi, stok, WO, pesanan, atau akun user production.

> Migration batch ini **tidak menghapus** transaksi, stok produk, work order, atau invoice. Yang dilakukan hanya:
> - menambah kolom / tabel baru
> - mengisi kolom baru (backfill `jenis_produk`, level mekanik)
> - **update** divisi/role karyawan legacy → struktur baru (produksi/warehouse → 77racing, Outlet → Staf, Super Admin → Head HR)
> - **menambah** akun login untuk mekanik yang belum punya `user_id` (data mekanik & WO tetap, `mekanik_id` tidak berubah)

#### Keamanan data — yang TIDAK disentuh migrate & seed

| Data | Migrate | `db:seed` |
|------|---------|-----------|
| Transaksi offline / warehouse | ✅ aman | ✅ tidak disentuh |
| Stok produk / WO / pesanan | ✅ aman | ✅ tidak disentuh |
| Akun user production | ✅ hanya update role | ✅ tidak disentuh |
| Kota / gudang / cabang | — | ✅ `firstOrCreate` / update nama |
| Tagihan Midtrans reseller | ✅ aman | ✅ tidak disentuh |

#### Migration yang perlu diperhatikan

| File | Dampak |
|------|--------|
| `2026_07_13_151700_backfill_lain_lain_brand_to_part_luar.php` | Update `products.jenis_produk` untuk brand Part Luar / LAIN-LAIN — **tidak hapus produk** |
| `2026_07_13_160000_migrate_superadmin_to_modifikasi_ori_head_hr.php` | Update akun `admin`/`SuperAdmin` saja → 77racing Head HR |
| `2026_07_14_000000_remove_legacy_division_role_users.php` | **NO-OP** — tidak menghapus user (aman untuk production) |
| `2026_07_14_010100_migrate_mekaniks_to_speedshop_mekanik_users.php` | **Insert** user baru hanya untuk mekanik tanpa `user_id`; link ke record mekanik yang sama |
| `2026_07_14_020000_migrate_users_to_new_role_structure.php` | **Update** semua user legacy ke divisi/role baru + pastikan mekanik ter-link (idempotent, aman production) |

> **Catatan:** Tidak perlu `migrate --path` per file kecuali ada masalah khusus. Urutan di bawah hanya referensi jika perlu dicek manual.

#### Urutan migration batch Juli 2026 (terbaru)

| No | File migration |
|----|----------------|
| 1 | `2026_07_11_010000_add_jenis_pembayaran_to_warehouse_transaksis.php` |
| 2 | `2026_07_11_020000_add_ongkir_to_transaksi_offline_table.php` |
| 3 | `2026_07_11_120000_create_warehouse_reseller_chats_tables.php` |
| 4 | `2026_07_11_122420_add_foto_profil_to_users_table.php` |
| 5 | `2026_07_11_125000_update_reseller_order_status_flow.php` |
| 6 | `2026_07_11_130000_add_return_fields_to_reseller_orders.php` |
| 7 | `2026_07_11_143000_add_packing_scan_to_reseller_orders.php` |
| 8 | `2026_07_11_160000_create_speedshop_chats_tables.php` |
| 9 | `2026_07_11_160000_create_reseller_vouchers_tables.php` |
| 10 | `2026_07_11_170000_create_app_direct_chats_tables.php` |
| 11 | `2026_07_11_180000_create_marketplace_orders_tables.php` |
| 12 | `2026_07_11_180000_create_warehouse_marketplace_chats_tables.php` |
| 13 | `2026_07_11_190000_create_marketplace_customer_addresses_table.php` |
| 14 | `2026_07_11_190000_create_marketplace_vouchers_tables.php` |
| 15 | `2026_07_11_200000_create_marketplace_product_likes_table.php` |
| 16 | `2026_07_11_200000_add_packing_scan_to_marketplace_orders.php` |
| 17 | `2026_07_13_120000_add_jenis_produk_to_products_table.php` |
| 18 | `2026_07_13_130000_add_bukti_resi_path_to_reseller_orders_table.php` |
| 19 | `2026_07_13_140000_add_tagihan_ids_to_reseller_tagihan_midtrans_payments.php` |
| 20 | `2026_07_13_151700_backfill_lain_lain_brand_to_part_luar.php` |
| 21 | `2026_07_13_160000_migrate_superadmin_to_modifikasi_ori_head_hr.php` |
| 22 | `2026_07_14_000000_remove_legacy_division_role_users.php` |
| 23 | `2026_07_14_010000_link_mekaniks_to_users_and_levels.php` |
| 24 | `2026_07_14_010100_migrate_mekaniks_to_speedshop_mekanik_users.php` |
| 25 | `2026_07_14_020000_migrate_users_to_new_role_structure.php` |

Cek status migration:

```bash
php artisan migrate:status
```

#### (Opsional) Jalankan per file — hanya jika `migrate` gagal di tengah

```bash
php artisan migrate --force --path=database/migrations/2026_07_11_010000_add_jenis_pembayaran_to_warehouse_transaksis.php
php artisan migrate --force --path=database/migrations/2026_07_11_020000_add_ongkir_to_transaksi_offline_table.php
php artisan migrate --force --path=database/migrations/2026_07_11_120000_create_warehouse_reseller_chats_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_122420_add_foto_profil_to_users_table.php
php artisan migrate --force --path=database/migrations/2026_07_11_125000_update_reseller_order_status_flow.php
php artisan migrate --force --path=database/migrations/2026_07_11_130000_add_return_fields_to_reseller_orders.php
php artisan migrate --force --path=database/migrations/2026_07_11_143000_add_packing_scan_to_reseller_orders.php
php artisan migrate --force --path=database/migrations/2026_07_11_160000_create_speedshop_chats_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_160000_create_reseller_vouchers_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_170000_create_app_direct_chats_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_180000_create_marketplace_orders_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_180000_create_warehouse_marketplace_chats_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_190000_create_marketplace_customer_addresses_table.php
php artisan migrate --force --path=database/migrations/2026_07_11_190000_create_marketplace_vouchers_tables.php
php artisan migrate --force --path=database/migrations/2026_07_11_200000_create_marketplace_product_likes_table.php
php artisan migrate --force --path=database/migrations/2026_07_11_200000_add_packing_scan_to_marketplace_orders.php
php artisan migrate --force --path=database/migrations/2026_07_13_120000_add_jenis_produk_to_products_table.php
php artisan migrate --force --path=database/migrations/2026_07_13_130000_add_bukti_resi_path_to_reseller_orders_table.php
php artisan migrate --force --path=database/migrations/2026_07_13_140000_add_tagihan_ids_to_reseller_tagihan_midtrans_payments.php
php artisan migrate --force --path=database/migrations/2026_07_13_151700_backfill_lain_lain_brand_to_part_luar.php
php artisan migrate --force --path=database/migrations/2026_07_13_160000_migrate_superadmin_to_modifikasi_ori_head_hr.php
php artisan migrate --force --path=database/migrations/2026_07_14_000000_remove_legacy_division_role_users.php
php artisan migrate --force --path=database/migrations/2026_07_14_010000_link_mekaniks_to_users_and_levels.php
php artisan migrate --force --path=database/migrations/2026_07_14_010100_migrate_mekaniks_to_speedshop_mekanik_users.php
php artisan migrate --force --path=database/migrations/2026_07_14_020000_migrate_users_to_new_role_structure.php
```

### 4. Seeder (`db:seed --force`)

Sudah dijalankan di langkah 3 (`migrate` lalu `db:seed`). Isi `DatabaseSeeder`:

| Seeder | Dampak |
|--------|--------|
| `KotaSeeder` | Tambah kota baru jika belum ada |
| `WarehouseSeeder` | Pastikan gudang Head Office ada |
| `SpeedshopSeeder` | Sinkron 4 cabang (KTP/CSK/CLK/JAS), update alamat jika perlu |
| `AkunOperasionalSeeder` | Master akun speedshop (`updateOrCreate` by `no_akun`) |
| `WarehouseAkunOperasionalSeeder` | Master akun laba rugi per gudang |

**Tidak ada** `truncate`, `delete` massal, atau insert transaksi.

#### (Opsional) Akun demo — hanya `APP_ENV=local`

```bash
php artisan db:seed --class=DivisionRoleUsersSeeder --force
```

> `DivisionRoleUsersSeeder` **tidak** masuk `DatabaseSeeder` default. Di production tidak dijalankan. Di local: hapus akun legacy demo (bukan mekanik ter-link WO), buat akun per role/cabang. Password akun yang **sudah ada** tidak di-reset.

### Format email demo

| Pola | Contoh |
|------|--------|
| Tanpa cabang | `owner.owner@`, `speedshop.manager@`, `modifikasi.headhr@` |
| Per cabang speedshop | `speedshop.accounting.ktp@`, `speedshop.flp.csk@`, `speedshop.staf.clk@`, `speedshop.mekanik.jas@` |

Role speedshop **Accounting, FLP, Staf, Mekanik** = **1 akun per cabang** (4 cabang → 4 akun per role). Filter `/admin/users?section=speedshop&role=Accounting` menampilkan 4 baris.

Kode cabang di email: `ktp`, `csk`, `clk`, `jas` (dari kode outlet di nama cabang).

### 5. Cache & storage

```bash
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Akun demo karyawan

Password semua akun demo: `password`

Contoh:
- Panel admin (Head HR): `modifikasi.headhr@modifikasiori.com`
- Speedshop Accounting Bandung: `speedshop.accounting.ktp@modifikasiori.com`
- Speedshop FLP Depok: `speedshop.flp.csk@modifikasiori.com`
- Owner: `owner.owner@modifikasiori.com`
