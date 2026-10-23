<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use League\Flysystem\UnableToWriteFile;

class ProfileController extends Controller
{
    public function __construct(
        private readonly AttendanceService $attendance,
    ) {}

    public function index(): View
    {
        $user = $this->staffUser();

        return view('profil.index', [
            'layout' => $this->attendance->layoutForUser($user),
            'user' => $user,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $this->staffUser();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'no_hp' => ['nullable', 'string', 'max:30'],
            'tanggal_lahir' => ['nullable', 'date', 'before:today', 'after:1900-01-01'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'tanggal_lahir.date' => 'Tanggal lahir tidak valid.',
            'tanggal_lahir.before' => 'Tanggal lahir harus sebelum hari ini.',
            'tanggal_lahir.after' => 'Tanggal lahir tidak valid.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
        ]);

        $payload = [
            'name' => trim((string) $validated['name']),
            'username' => filled($validated['username'] ?? null)
                ? trim((string) $validated['username'])
                : null,
            'email' => trim((string) $validated['email']),
            'no_hp' => filled($validated['no_hp'] ?? null)
                ? trim((string) $validated['no_hp'])
                : null,
            'tanggal_lahir' => filled($validated['tanggal_lahir'] ?? null)
                ? $validated['tanggal_lahir']
                : null,
        ];

        if (! empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        $user->update($payload);

        if ($user->isSpeedshopMekanik() && $user->mekanik) {
            $user->mekanik->update([
                'nama' => $payload['name'],
                'no_hp' => $payload['no_hp'],
            ]);
        }

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function updateFoto(Request $request): RedirectResponse
    {
        $user = $this->staffUser();

        $request->validate([
            'foto_profil' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'foto_profil.required' => 'Pilih foto profil terlebih dahulu.',
            'foto_profil.image' => 'File harus berupa gambar.',
            'foto_profil.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
            'foto_profil.max' => 'Ukuran foto maksimal 2 MB.',
        ]);

        $disk = (string) config('filesystems.upload_disk', 'public');
        $prefix = 'karyawan/profil/'.$user->id;
        $oldPath = $user->foto_profil;

        try {
            $path = $request->file('foto_profil')->store($prefix, $disk);
        } catch (UnableToWriteFile $e) {
            return back()->with('error', 'Gagal mengunggah foto ke penyimpanan. Coba lagi.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengunggah foto profil. Coba lagi.');
        }

        $user->update(['foto_profil' => $path]);

        if ($oldPath && $oldPath !== $path) {
            try {
                Storage::disk($disk)->delete($oldPath);
            } catch (\Throwable $e) {
                // Abaikan gagal hapus file lama.
            }
        }

        return back()->with('success', 'Foto profil berhasil diperbarui.');
    }

    public function hapusFoto(): RedirectResponse
    {
        $user = $this->staffUser();
        if (! $user->foto_profil) {
            return back()->with('error', 'Belum ada foto profil.');
        }

        $disk = (string) config('filesystems.upload_disk', 'public');
        $oldPath = $user->foto_profil;
        $user->update(['foto_profil' => null]);

        try {
            Storage::disk($disk)->delete($oldPath);
        } catch (\Throwable $e) {
            // Abaikan gagal hapus file.
        }

        return back()->with('success', 'Foto profil dihapus.');
    }

    private function staffUser(): User
    {
        $user = auth()->user();
        abort_if($user === null, 403);
        abort_if($user->isMarketplaceCustomer() || $user->isReseller(), 403);

        return $user;
    }
}
