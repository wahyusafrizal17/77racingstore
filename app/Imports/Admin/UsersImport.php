<?php

namespace App\Imports\Admin;

use App\Models\MekanikLevel;
use App\Models\User;
use App\Services\MekanikUserSyncService;
use App\Support\AdminUserPayload;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToCollection, WithHeadingRow
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $deleted = 0;

    public int $deleteSkipped = 0;

    /** @var list<string> */
    public array $errors = [];

    /** @var list<string> */
    public array $deleteErrors = [];

    public function __construct(
        private readonly array $warehouseIdsByName,
        private readonly array $speedshopIdsByName,
        private readonly array $mekanikLevelIdsByName = [],
        private readonly bool $syncReplace = false,
        private readonly ?int $protectedUserId = null,
    ) {
    }

    public function collection(Collection $rows): void
    {
        /** @var list<array{row: int, payload: array<string, mixed>, is_mekanik: bool, mekanik_level_id: ?int, no_hp: ?string, existing_id: ?int}> $ready */
        $ready = [];
        $rowNum = 1;
        /** @var array<string, int> $emailsSeen */
        $emailsSeen = [];

        foreach ($rows as $row) {
            $rowNum++;
            $prepared = $this->prepareRow($row, $rowNum);
            if ($prepared === null) {
                continue;
            }
            if (isset($prepared['_error'])) {
                $this->skip($rowNum, (string) $prepared['_error']);

                continue;
            }

            $emailKey = mb_strtolower((string) ($prepared['payload']['email'] ?? ''));
            if ($emailKey !== '') {
                if (isset($emailsSeen[$emailKey])) {
                    $this->skip(
                        $rowNum,
                        'Email duplikat di file (sama dengan baris '.$emailsSeen[$emailKey].'): '.$prepared['payload']['email']
                    );

                    continue;
                }
                $emailsSeen[$emailKey] = $rowNum;
            }

            $ready[] = $prepared;
        }

        // Sinkron penuh hanya jalan jika semua baris valid.
        if ($this->syncReplace && $this->skipped > 0) {
            $this->errors = array_values(array_merge(
                ['Sinkron penuh dibatalkan karena ada baris error. Tidak ada data yang diubah.'],
                $this->errors,
            ));

            return;
        }

        if ($ready === []) {
            if ($this->syncReplace) {
                $this->errors[] = 'Sinkron penuh dibatalkan: file tidak berisi baris karyawan yang valid.';
            }

            return;
        }

        $importedEmails = [];

        DB::transaction(function () use ($ready, &$importedEmails) {
            foreach ($ready as $item) {
                $payload = $item['payload'];
                $existing = $item['existing_id']
                    ? User::query()->find($item['existing_id'])
                    : null;

                if ($existing) {
                    $existing->update($payload);
                    $user = $existing->fresh();
                    $this->updated++;
                } else {
                    $user = User::create($payload);
                    $this->created++;
                }

                $importedEmails[] = mb_strtolower((string) $user->email);

                MekanikUserSyncService::syncFromUser(
                    $user,
                    $item['is_mekanik'] ? $item['mekanik_level_id'] : null,
                    $item['is_mekanik'] ? $item['no_hp'] : null,
                );

                if (($payload['section'] ?? '') === 'speedshop') {
                    $user->syncAssignedSpeedshops($item['speedshop_ids'] ?? []);
                } else {
                    $user->syncAssignedSpeedshops([]);
                }
            }

            if ($this->syncReplace) {
                $this->purgeUsersNotInImport($importedEmails);
            }
        });
    }

    /**
     * @param  list<string>  $importedEmailsLower
     */
    private function purgeUsersNotInImport(array $importedEmailsLower): void
    {
        $emails = array_values(array_unique(array_filter($importedEmailsLower)));

        $candidates = User::query()
            ->adminKaryawan()
            ->with('mekanik')
            ->when($this->protectedUserId, fn ($q) => $q->where('id', '!=', $this->protectedUserId))
            ->when($emails !== [], function ($q) use ($emails) {
                $q->where(function ($inner) use ($emails) {
                    $inner->whereNull('email')
                        ->orWhereRaw('LOWER(email) NOT IN ('.implode(',', array_fill(0, count($emails), '?')).')', $emails);
                });
            })
            ->orderBy('id')
            ->get();

        foreach ($candidates as $user) {
            if ($this->protectedUserId && (int) $user->id === (int) $this->protectedUserId) {
                continue;
            }

            if ($user->canAccessAdminPanel()) {
                $other = User::adminPanelUserQuery()
                    ->where('id', '!=', $user->id)
                    ->exists();
                if (! $other) {
                    $this->deleteSkipped++;
                    $this->pushDeleteError('Tidak menghapus '.$user->email.' (harus ada minimal satu akun yang bisa membuka panel admin).');

                    continue;
                }
            }

            // Sinkron penuh: lepas link mekanik (WO tetap aman), lalu hapus akun login lama.
            MekanikUserSyncService::releaseUserForSyncDeletion($user);
            $user->delete();
            $this->deleted++;
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function prepareRow(mixed $row, int $rowNum): ?array
    {
        $data = $this->parseRow($row);
        if ($data === null) {
            return null;
        }
        if (isset($data['_error'])) {
            return $data;
        }

        $existing = $data['email'] !== ''
            ? User::query()->with('mekanik')
                ->whereRaw('LOWER(email) = ?', [mb_strtolower($data['email'])])
                ->first()
            : null;

        $payload = [
            'name' => $data['name'],
            'username' => $data['username'] !== '' ? $data['username'] : null,
            'email' => $data['email'],
            'section' => $data['section'],
            'role' => $data['role'],
            'warehouse_id' => $data['warehouse_id'],
            'speedshop_id' => $data['speedshop_id'],
            'speedshop_ids' => $data['speedshop_ids'] ?? [],
        ];

        $payload = AdminUserPayload::normalize($payload);

        if ($msg = AdminUserPayload::sectionAssignmentError($payload)) {
            return ['_error' => $msg];
        }

        $speedshopIds = collect($payload['speedshop_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
        unset($payload['speedshop_ids']);

        if ($payload['section'] === 'speedshop'
            && ! in_array($payload['role'], AdminUserPayload::speedshopRolesWithMultiCabang(), true)
            && count($speedshopIds) > 1) {
            return ['_error' => 'Role '.$payload['role'].' hanya boleh satu cabang. Gunakan Accounting/Head Area untuk multi-cabang.'];
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'section' => ['required', 'in:admin,owner,speedshop,'.User::SECTION_MODIFIKASI_ORI.',produksi,warehouse'],
            'role' => ['required', 'string'],
        ];

        $roles = AdminUserPayload::rolesForSection($payload['section']);
        if (! in_array($payload['role'], $roles, true)) {
            return ['_error' => 'Role tidak valid untuk divisi '.$payload['section'].'. Role yang diizinkan: '.implode(', ', $roles).'.'];
        }

        $isMekanik = $payload['section'] === 'speedshop'
            && $payload['role'] === User::ROLE_SPEEDSHOP_MEKANIK;
        $mekanikLevelId = $data['mekanik_level_id'];
        $noHp = $data['no_hp'] !== '' ? $data['no_hp'] : null;

        if ($isMekanik) {
            if ($mekanikLevelId === null && $existing?->mekanik?->mekanik_level_id) {
                $mekanikLevelId = (int) $existing->mekanik->mekanik_level_id;
            }
            if ($mekanikLevelId === null) {
                return ['_error' => 'Role Mekanik wajib mengisi Level Mekanik (nama level harus sama seperti master Level Mekanik).'];
            }
            if ($noHp === null && $existing?->mekanik?->no_hp) {
                $noHp = (string) $existing->mekanik->no_hp;
            }
        }

        if ($existing) {
            $rules['email'][] = 'unique:users,email,'.$existing->id;
            if ($payload['username']) {
                $rules['username'][] = 'unique:users,username,'.$existing->id;
            }
        } else {
            $rules['email'][] = 'unique:users,email';
            if ($payload['username']) {
                $rules['username'][] = 'unique:users,username';
            }
            // Excel sering tanpa kolom Password — pakai default template.
            if ($data['password'] === '') {
                $data['password'] = 'password123';
            }
        }

        $validator = Validator::make($payload, $rules);
        if ($validator->fails()) {
            return ['_error' => implode(' ', $validator->errors()->all())];
        }

        if ($data['password'] !== '' && mb_strlen($data['password']) < 8) {
            return ['_error' => 'Password minimal 8 karakter.'];
        }

        if ($existing && $existing->canAccessAdminPanel() && ! User::grantsAdminPanelAccess($payload['section'], $payload['role'])) {
            $other = User::adminPanelUserQuery()
                ->where('id', '!=', $existing->id)
                ->exists();
            if (! $other) {
                return ['_error' => 'Harus ada minimal satu akun yang bisa membuka panel admin.'];
            }
        }

        if ($data['password'] !== '') {
            $payload['password'] = $data['password'];
        }

        return [
            'row' => $rowNum,
            'payload' => $payload,
            'speedshop_ids' => $speedshopIds,
            'is_mekanik' => $isMekanik,
            'mekanik_level_id' => $mekanikLevelId,
            'no_hp' => $noHp,
            'existing_id' => $existing?->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function parseRow(mixed $row): ?array
    {
        $row = is_array($row) ? $row : $row->toArray();

        $name = trim((string) ($row['nama'] ?? $row['name'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));
        $section = strtolower(trim((string) ($row['divisi'] ?? $row['section'] ?? '')));
        $section = match ($section) {
            'owner' => User::SECTION_OWNER,
            'speedshop' => 'speedshop',
            'modifikasi ori', 'modifikasiori', '77racing', 'modifikasi_ori' => User::SECTION_MODIFIKASI_ORI,
            'produksi' => 'produksi',
            'warehouse', 'gudang' => 'warehouse',
            'admin' => User::SECTION_MODIFIKASI_ORI,
            default => $section,
        };
        $roleRaw = trim((string) ($row['role'] ?? ''));

        // Legacy SuperAdmin → Head HR 77racing (bukan role speedshop).
        if (in_array(mb_strtolower($roleRaw), ['superadmin', 'super admin'], true)) {
            $section = User::SECTION_MODIFIKASI_ORI;
            $roleRaw = User::ROLE_MODIFIKASI_HEAD_HR;
        }

        $role = $this->normalizeRole($section, $roleRaw);

        if ($name === '' && $email === '' && $section === '' && $roleRaw === '') {
            return null;
        }

        $username = trim((string) ($row['username'] ?? ''));
        $password = (string) ($row['password'] ?? '');

        $gudang = trim((string) ($row['gudang'] ?? $row['warehouse'] ?? ''));
        $cabang = trim((string) ($row['cabang'] ?? $row['speedshop'] ?? ''));

        if ($this->isAllLocationsLabel($cabang)) {
            $cabang = '';
        }
        if ($this->isAllLocationsLabel($gudang)) {
            $gudang = '';
        }

        $warehouseId = $this->resolveId($gudang, $this->warehouseIdsByName);
        $speedshopIds = $this->resolveSpeedshopIds($cabang);
        $speedshopId = $speedshopIds[0] ?? null;

        if ($gudang !== '' && $warehouseId === null) {
            return ['_error' => 'Gudang tidak ditemukan: '.$gudang];
        }
        if ($cabang !== '' && $speedshopIds === []) {
            return ['_error' => 'Cabang tidak ditemukan: '.$cabang];
        }

        $levelName = trim((string) (
            $row['level_mekanik']
            ?? $row['level']
            ?? $row['mekanik_level']
            ?? ''
        ));
        $noHp = trim((string) ($row['no_hp'] ?? $row['nohp'] ?? $row['hp'] ?? ''));

        $mekanikLevelId = null;
        if ($levelName !== '') {
            $mekanikLevelId = $this->resolveId($levelName, $this->mekanikLevelIdsByName);
            if ($mekanikLevelId === null) {
                $existingLevel = MekanikLevel::query()
                    ->whereRaw('LOWER(nama) = ?', [mb_strtolower($levelName)])
                    ->first();
                $mekanikLevelId = (int) ($existingLevel ?? MekanikLevel::query()->create(['nama' => $levelName]))->id;
            }
        }

        return [
            'name' => $name,
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'section' => $section,
            'role' => $role,
            'warehouse_id' => $warehouseId,
            'speedshop_id' => $speedshopId,
            'speedshop_ids' => $speedshopIds,
            'mekanik_level_id' => $mekanikLevelId,
            'no_hp' => $noHp,
        ];
    }

    /** @return list<int> */
    private function resolveSpeedshopIds(string $cabangRaw): array
    {
        $cabangRaw = trim($cabangRaw);
        if ($cabangRaw === '') {
            return [];
        }

        $parts = preg_split('/\s*(?:&|,|;|\||\bdan\b)\s*/iu', $cabangRaw) ?: [];
        $ids = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            $id = $this->resolveId($part, $this->speedshopIdsByName);
            if ($id === null) {
                return [];
            }
            $ids[] = $id;
        }

        return array_values(array_unique($ids));
    }

    private function normalizeRole(string $section, string $role): string
    {
        if ($role === '') {
            return '';
        }

        $roles = AdminUserPayload::rolesForSection($section);
        if ($roles === []) {
            return $role;
        }

        foreach ($roles as $allowed) {
            if (strcasecmp($allowed, $role) === 0) {
                return $allowed;
            }
        }

        $aliases = [
            'outlet' => 'Staf',
            'head area' => User::ROLE_SPEEDSHOP_HEAD_AREA,
            'head area speedshop' => User::ROLE_SPEEDSHOP_HEAD_AREA,
            'manager speedshop' => 'Manager',
            'manager modifikasi ori' => 'Manager',
            'manager modifikasi_ori' => 'Manager',
            'mekanik' => User::ROLE_SPEEDSHOP_MEKANIK,
            'flp' => User::ROLE_SPEEDSHOP_FLP,
            'head hr' => User::ROLE_MODIFIKASI_HEAD_HR,
            'staf hr' => User::ROLE_MODIFIKASI_STAF_HR,
            'head audit' => User::ROLE_MODIFIKASI_HEAD_AUDIT,
            'superadmin' => User::ROLE_MODIFIKASI_HEAD_HR,
            'super admin' => User::ROLE_MODIFIKASI_HEAD_HR,
            'head warehouse' => User::ROLE_MODIFIKASI_HEAD_WAREHOUSE,
            'staf warehouse' => User::ROLE_WAREHOUSE_STAF_WAREHOUSE,
            'staf inventori' => User::ROLE_MODIFIKASI_STAF_INVENTORI,
            'staf packing' => User::ROLE_MODIFIKASI_STAF_INVENTORI,
            'staf packaging' => User::ROLE_MODIFIKASI_STAF_INVENTORI,
            'head produksi' => User::ROLE_MODIFIKASI_HEAD_PRODUKSI,
            'staf produksi' => User::ROLE_MODIFIKASI_STAF_PRODUKSI,
            'head marketing' => User::ROLE_MODIFIKASI_HEAD_MARKETING,
            'staf marketing' => User::ROLE_MODIFIKASI_STAF_MARKETING,
            'head accounting' => User::ROLE_MODIFIKASI_HEAD_ACCOUNTING,
            'staf accounting' => User::ROLE_MODIFIKASI_STAF_ACCOUNTING,
            'staf processing warehouse' => User::ROLE_MODIFIKASI_STAF_PROCESSING_WAREHOUSE,
        ];

        $key = mb_strtolower(trim($role));
        if (isset($aliases[$key]) && in_array($aliases[$key], $roles, true)) {
            return $aliases[$key];
        }

        $stripped = preg_replace('/\s+(speedshop|modifikasi\s*ori|modifikasi_ori)$/iu', '', $role) ?? $role;
        $stripped = trim($stripped);
        if ($stripped !== '' && strcasecmp($stripped, $role) !== 0) {
            foreach ($roles as $allowed) {
                if (strcasecmp($allowed, $stripped) === 0) {
                    return $allowed;
                }
            }
            $strippedKey = mb_strtolower($stripped);
            if (isset($aliases[$strippedKey]) && in_array($aliases[$strippedKey], $roles, true)) {
                return $aliases[$strippedKey];
            }
        }

        return $role;
    }

    private function isAllLocationsLabel(string $value): bool
    {
        $key = mb_strtolower(trim($value));
        if ($key === '') {
            return false;
        }

        return in_array($key, [
            'semua cabang',
            'semua gudang',
            'semua divisi',
            'semua divisi modifikasi ori',
            'semua divisi modifikasi_ori',
            '-',
        ], true);
    }

    private function resolveId(string $name, array $map): ?int
    {
        if ($name === '') {
            return null;
        }

        $key = mb_strtolower($name);

        return isset($map[$key]) ? (int) $map[$key] : null;
    }

    private function skip(int $rowNum, string $message): void
    {
        $this->skipped++;
        if (count($this->errors) < 20) {
            $this->errors[] = 'Baris '.$rowNum.': '.$message;
        }
    }

    private function pushDeleteError(string $message): void
    {
        if (count($this->deleteErrors) < 10) {
            $this->deleteErrors[] = $message;
        }
    }
}
