<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Head Audit: lihat data keuangan saja (tanpa tambah, ubah, hapus, import, verifikasi).
 * Pengecualian: boleh menyimpan keterangan temuan audit (Deskripsi / Penyelesaian) pada biaya operasional.
 */
class RestrictHeadAuditFinanceMutations
{
    /** @var list<string> */
    private const MUTATION_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /** @var list<string> */
    private const ALLOWED_MUTATION_ROUTES = [
        'speedshop.keuangan.biaya-operasional.audit-temuan',
        'warehouse.keuangan.biaya-operasional.audit-temuan',
    ];

    /** @var list<string> */
    private const BLOCKED_GET_ROUTE_SUFFIXES = [
        '.create',
        '.edit',
        '.import',
        '.template',
    ];

    /** @var list<string> */
    private const BLOCKED_GET_ROUTE_FRAGMENTS = [
        '.approve',
        '.reject',
        '.bayar',
        '.konfirmasi',
        '.store',
        '.update',
        '.destroy',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || ! $user->isModifikasiOriHeadAudit()) {
            return $next($request);
        }

        $name = $request->route()?->getName() ?? '';

        if (in_array($request->method(), self::MUTATION_METHODS, true)) {
            if ($name !== '' && in_array($name, self::ALLOWED_MUTATION_ROUTES, true)) {
                return $next($request);
            }
            abort(403, 'Head Audit hanya dapat melihat data (tanpa tambah, ubah, atau hapus).');
        }

        foreach (self::BLOCKED_GET_ROUTE_SUFFIXES as $suffix) {
            if ($name !== '' && str_ends_with($name, $suffix)) {
                abort(403, 'Head Audit hanya dapat melihat data (tanpa tambah, ubah, atau hapus).');
            }
        }

        foreach (self::BLOCKED_GET_ROUTE_FRAGMENTS as $fragment) {
            if ($name !== '' && str_contains($name, $fragment)) {
                abort(403, 'Head Audit hanya dapat melihat data (tanpa tambah, ubah, atau hapus).');
            }
        }

        return $next($request);
    }
}
