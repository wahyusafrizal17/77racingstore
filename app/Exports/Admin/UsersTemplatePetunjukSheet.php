<?php

namespace App\Exports\Admin;

use App\Support\AdminUserPayload;
use App\Models\User;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class UsersTemplatePetunjukSheet implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function title(): string
    {
        return 'Petunjuk';
    }

    public function headings(): array
    {
        return ['Kolom', 'Keterangan'];
    }

    public function array(): array
    {
        $speedshopRoles = implode(', ', AdminUserPayload::speedshopRoles());
        $modifikasiRoles = implode(', ', AdminUserPayload::modifikasiOriRoles());

        return [
            ['Nama', 'Wajib. Nama lengkap karyawan.'],
            ['Username', 'Opsional. Harus unik jika diisi.'],
            ['Email', 'Wajib. Unik. Dipakai untuk update jika email sudah ada.'],
            ['Divisi', 'Wajib. Isi: owner | speedshop | modifikasi_ori (boleh juga label: Owner, Speedshop, 77racing).'],
            ['Role', 'Wajib. Harus sesuai divisi. Lihat daftar role di bawah.'],
            ['Gudang', 'Opsional. Nama gudang persis seperti di sistem (untuk role 77racing yang terikat gudang).'],
            ['Cabang', 'Nama cabang Speedshop persis seperti di sistem. Accounting/Head Area: bisa multi-cabang dipisah koma/titik koma. Manager: isi satu cabang atau "Semua Cabang"/kosong. Mekanik/FLP/Staf: wajib satu cabang.'],
            ['Level Mekanik', 'Wajib jika Role = Mekanik. Pilih dari dropdown: KEPALA MEKANIK / MEKANIK LEVEL 1 (sesuai master Level Mekanik). Kosongkan untuk role lain.'],
            ['No HP', 'Opsional. Hanya dipakai untuk Role Mekanik (disimpan ke profil mekanik).'],
            ['Password', 'Wajib untuk user baru (min. 8 karakter). Kosongkan saat update agar password tidak berubah.'],
            ['', ''],
            ['Role Owner', User::ROLE_OWNER],
            ['Role Speedshop', $speedshopRoles],
            ['Role 77racing', $modifikasiRoles],
            ['', ''],
            ['Catatan Mekanik', 'Import Role Mekanik otomatis membuat/menyinkronkan data di master Mekanik (untuk assign Work Order).'],
            ['Catatan', 'Hapus baris contoh sebelum import jika tidak dibutuhkan. Format file .xlsx / .xls.'],
        ];
    }
}
