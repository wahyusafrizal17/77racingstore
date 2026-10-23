<?php

namespace Database\Seeders;

use App\Models\MekanikLevel;
use App\Models\Speedshop;
use App\Models\User;
use App\Services\MekanikUserSyncService;
use App\Support\AdminUserPayload;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DivisionRoleUsersSeeder extends Seeder
{
    /**
     * Akun demo karyawan resmi:
     * - Owner (1) + 77racing (13) + Speedshop regional (Manager, Head Area)
     * - Speedshop per cabang: Accounting, FLP, Staf, Mekanik (4 cabang × 4 role = 16)
     *
     * Email per cabang: speedshop.{role}.{kode-cabang}@modifikasiori.com — password: password
     * Email tanpa cabang: {divisi}.{role}@modifikasiori.com
     */
    public function run(): void
    {
        $speedshops = Speedshop::query()->orderBy('id')->get();

        if ($speedshops->isEmpty()) {
            $this->command->warn('DivisionRoleUsersSeeder: speedshop belum ada, jalankan SpeedshopSeeder dulu.');

            return;
        }

        $accounts = $this->accounts($speedshops);
        $keepEmails = array_column($accounts, 'email');

        $removed = $this->purgeLegacyDivisionUsers($keepEmails);

        foreach ($accounts as $account) {
            $user = User::query()->firstOrNew(['email' => $account['email']]);

            if (! $user->exists) {
                $user->fill($account)->save();
            } else {
                $user->update(collect($account)->except(['password', 'email'])->all());
            }

            if ($user->isSpeedshopMekanik()) {
                $levelId = MekanikLevel::query()->orderBy('nama')->value('id');
                MekanikUserSyncService::syncFromUser($user, $levelId ? (int) $levelId : null);
            }
        }

        $this->command->info(sprintf(
            'DivisionRoleUsersSeeder: %d akun role aktif, %d akun legacy dihapus (password: password).',
            count($accounts),
            $removed,
        ));
    }

    /** @param list<string> $keepEmails */
    private function purgeLegacyDivisionUsers(array $keepEmails): int
    {
        if (! app()->environment('local')) {
            $this->command?->warn('DivisionRoleUsersSeeder: lewati hapus akun legacy (hanya di APP_ENV=local).');

            return 0;
        }

        $purgeSections = [
            User::SECTION_OWNER,
            'speedshop',
            User::SECTION_MODIFIKASI_ORI,
            'produksi',
            'warehouse',
            'admin',
        ];

        return User::query()
            ->whereIn('section', $purgeSections)
            ->whereNotIn('email', $keepEmails)
            ->whereDoesntHave('mekanik')
            ->delete();
    }

    /**
     * @param  Collection<int, Speedshop>  $speedshops
     * @return list<array<string, mixed>>
     */
    private function accounts(Collection $speedshops): array
    {
        $password = Hash::make('password');
        $accounts = [];

        $allowedRoles = ['Manager', 'Accounting', 'Staf', User::ROLE_SPEEDSHOP_FLP, User::ROLE_SPEEDSHOP_MEKANIK];

        foreach (AdminUserPayload::rolesBySectionForAdmin() as $section => $roles) {
            if ($section !== 'speedshop') {
                continue;
            }

            $divisiSlug = $this->divisiSlug($section);

            foreach ($roles as $role) {
                if (! in_array($role, $allowedRoles, true)) {
                    continue;
                }
                if ($section === 'speedshop' && in_array($role, AdminUserPayload::speedshopRolesPerCabang(), true)) {
                    foreach ($speedshops as $speedshop) {
                        $cabangSlug = $this->speedshopCabangSlug($speedshop);
                        $roleSlug = $this->roleSlug($role);
                        $accounts[] = $this->buildAccount(
                            divisiSlug: $divisiSlug,
                            roleSlug: $roleSlug,
                            cabangSlug: $cabangSlug,
                            label: $this->divisiLabel($section).' — '.$role.' — '.$speedshop->nama,
                            password: $password,
                            section: $section,
                            role: $role,
                            warehouseId: null,
                            speedshopId: (int) $speedshop->id,
                        );
                    }

                    continue;
                }

                $roleSlug = $this->roleSlug($role);
                $accounts[] = $this->buildAccount(
                    divisiSlug: $divisiSlug,
                    roleSlug: $roleSlug,
                    cabangSlug: null,
                    label: $this->divisiLabel($section).' — '.$role,
                    password: $password,
                    section: $section,
                    role: $role,
                    warehouseId: null,
                    speedshopId: $this->resolveSpeedshopId($section, $role, $speedshops->first()->id),
                );
            }
        }

        return $accounts;
    }

    /** @return array<string, mixed> */
    private function buildAccount(
        string $divisiSlug,
        string $roleSlug,
        ?string $cabangSlug,
        string $label,
        string $password,
        string $section,
        string $role,
        ?int $warehouseId,
        ?int $speedshopId,
    ): array {
        $localPart = $cabangSlug
            ? "{$divisiSlug}.{$roleSlug}.{$cabangSlug}"
            : "{$divisiSlug}.{$roleSlug}";

        return [
            'name' => $label,
            'username' => Str::limit($localPart, 100, ''),
            'email' => $localPart.'@77racingstore.com',
            'password' => $password,
            'section' => $section,
            'role' => $role,
            'warehouse_id' => $warehouseId,
            'speedshop_id' => $speedshopId,
        ];
    }

    private function speedshopCabangSlug(Speedshop $speedshop): string
    {
        if (preg_match('/-\s*([A-Za-z0-9]+)\s*$/', (string) $speedshop->nama, $matches)) {
            return Str::lower($matches[1]);
        }

        return 'cabang'.$speedshop->id;
    }

    private function divisiSlug(string $section): string
    {
        return match ($section) {
            User::SECTION_OWNER => 'owner',
            'speedshop' => 'speedshop',
            User::SECTION_MODIFIKASI_ORI => 'modifikasi',
            default => Str::slug($section),
        };
    }

    private function divisiLabel(string $section): string
    {
        return match ($section) {
            User::SECTION_OWNER => 'Owner',
            'speedshop' => 'Speedshop',
            User::SECTION_MODIFIKASI_ORI => '77racing',
            default => ucfirst($section),
        };
    }

    private function roleSlug(string $role): string
    {
        return Str::slug(str_replace(['/', '\\'], '-', $role), '');
    }

    private function resolveSpeedshopId(string $section, string $role, int $speedshopId): ?int
    {
        if ($section !== 'speedshop') {
            return null;
        }

        return in_array($role, AdminUserPayload::speedshopRolesRequiringCabang(), true)
            ? $speedshopId
            : null;
    }
}
