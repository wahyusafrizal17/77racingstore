<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $speedshopId = DB::table('speedshops')->orderBy('id')->value('id');

        if (! $speedshopId) {
            return;
        }

        $defaultPassword = Hash::make('password');
        $now = now();

        foreach (DB::table('mekaniks')->whereNull('user_id')->orderBy('id')->get() as $mekanik) {
            // Jangan buat user baru jika email/nama sudah ada — hindari duplikat & ganggu akun produksi.
            $existingUser = DB::table('users')
                ->where('email', 'like', '%@modifikasiori.com')
                ->where(function ($query) use ($mekanik) {
                    $query->where('name', $mekanik->nama)
                        ->orWhere('email', 'like', '%.'.Str::slug((string) $mekanik->nama, '.').'%@modifikasiori.com');
                })
                ->first();

            if ($existingUser) {
                DB::table('mekaniks')->where('id', $mekanik->id)->update([
                    'user_id' => $existingUser->id,
                    'speedshop_id' => $existingUser->speedshop_id ?? $speedshopId,
                    'updated_at' => $now,
                ]);

                continue;
            }

            $base = 'speedshop.mekanik.'.Str::slug((string) $mekanik->nama, '.');
            if ($base === 'speedshop.mekanik.') {
                $base = 'speedshop.mekanik.'.$mekanik->id;
            }

            $email = $this->uniqueEmail($base);
            $username = $this->uniqueUsername(str_replace('@modifikasiori.com', '', $email));

            $userId = DB::table('users')->insertGetId([
                'name' => $mekanik->nama,
                'username' => $username,
                'email' => $email,
                'password' => $defaultPassword,
                'role' => User::ROLE_SPEEDSHOP_MEKANIK,
                'section' => 'speedshop',
                'warehouse_id' => null,
                'speedshop_id' => $speedshopId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('mekaniks')->where('id', $mekanik->id)->update([
                'user_id' => $userId,
                'speedshop_id' => $speedshopId,
                'updated_at' => $now,
            ]);
        }

        // Sinkronkan akun demo speedshop.mekanik@ jika belum punya profil mekanik.
        $demoUser = DB::table('users')
            ->where('email', 'speedshop.mekanik@modifikasiori.com')
            ->where('role', User::ROLE_SPEEDSHOP_MEKANIK)
            ->first();

        if ($demoUser && ! DB::table('mekaniks')->where('user_id', $demoUser->id)->exists()) {
            $levelId = DB::table('mekanik_levels')->orderBy('nama')->value('id');
            $levelName = $levelId
                ? DB::table('mekanik_levels')->where('id', $levelId)->value('nama')
                : null;

            DB::table('mekaniks')->insert([
                'user_id' => $demoUser->id,
                'mekanik_level_id' => $levelId,
                'speedshop_id' => $demoUser->speedshop_id ?? $speedshopId,
                'nama' => $demoUser->name,
                'no_hp' => null,
                'spesialisasi' => $levelName,
                'status' => 'aktif',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        $emails = DB::table('mekaniks')
            ->whereNotNull('user_id')
            ->join('users', 'users.id', '=', 'mekaniks.user_id')
            ->where('users.email', 'like', 'speedshop.mekanik.%@modifikasiori.com')
            ->where('users.email', '!=', 'speedshop.mekanik@modifikasiori.com')
            ->pluck('users.email');

        DB::table('mekaniks')->whereNotNull('user_id')->update([
            'user_id' => null,
            'speedshop_id' => null,
        ]);

        if ($emails->isNotEmpty()) {
            DB::table('users')->whereIn('email', $emails)->delete();
        }
    }

    private function uniqueEmail(string $base): string
    {
        $email = $base.'@modifikasiori.com';
        $suffix = 1;

        while (DB::table('users')->where('email', $email)->exists()) {
            $email = $base.'.'.$suffix.'@modifikasiori.com';
            $suffix++;
        }

        return $email;
    }

    private function uniqueUsername(string $base): string
    {
        $username = Str::limit($base, 100, '');
        $suffix = 1;

        while (DB::table('users')->where('username', $username)->exists()) {
            $username = Str::limit($base, 95, '').'.'.$suffix;
            $suffix++;
        }

        return $username;
    }
};
