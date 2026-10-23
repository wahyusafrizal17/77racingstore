<?php

namespace App\Support;

use Carbon\Carbon;
use Illuminate\Http\Request;

final class DashboardPeriodResolver
{
    /**
     * Periode dashboard / laporan (harian, bulanan, tahunan, range).
     *
     * @return array{
     *     dari: Carbon,
     *     sampai: Carbon,
     *     mode: string,
     *     selectedDate: string,
     *     selectedMonth: string,
     *     selectedYear: int,
     *     selectedDari: string,
     *     selectedSampai: string
     * }
     */
    public static function fromRequest(Request $request, string $defaultMode = 'harian'): array
    {
        $mode = in_array($request->input('mode'), ['harian', 'bulanan', 'tahunan', 'range', 'semua'], true)
            ? $request->input('mode')
            : (in_array($defaultMode, ['harian', 'bulanan', 'tahunan', 'range', 'semua'], true) ? $defaultMode : 'harian');

        if ($request->filled('dari') && $request->filled('sampai') && ! $request->filled('mode')) {
            $mode = 'range';
        }

        $selectedDate = $request->input('tanggal') ?: now()->format('Y-m-d');
        $selectedMonth = $request->input('bulan') ?: now()->format('Y-m');
        $selectedYear = (int) ($request->input('tahun') ?: now()->year);
        $selectedDari = $request->input('dari') ?: now()->startOfMonth()->format('Y-m-d');
        $selectedSampai = $request->input('sampai') ?: now()->format('Y-m-d');

        if ($mode === 'semua') {
            return [
                'dari' => Carbon::create(2000, 1, 1)->startOfDay(),
                'sampai' => now()->copy()->addYear()->endOfDay(),
                'mode' => 'semua',
                'selectedDate' => $selectedDate,
                'selectedMonth' => $selectedMonth,
                'selectedYear' => $selectedYear,
                'selectedDari' => $selectedDari,
                'selectedSampai' => $selectedSampai,
            ];
        }

        if ($mode === 'range') {
            try {
                $dari = Carbon::parse($selectedDari)->startOfDay();
                $sampai = Carbon::parse($selectedSampai)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDari = now()->startOfMonth()->format('Y-m-d');
                $selectedSampai = now()->format('Y-m-d');
                $dari = now()->startOfMonth()->startOfDay();
                $sampai = now()->endOfDay();
            }

            if ($dari->gt($sampai)) {
                [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
                $selectedDari = $dari->format('Y-m-d');
                $selectedSampai = $sampai->format('Y-m-d');
            }

            return [
                'dari' => $dari,
                'sampai' => $sampai,
                'mode' => 'range',
                'selectedDate' => $selectedDari,
                'selectedMonth' => $dari->format('Y-m'),
                'selectedYear' => (int) $dari->format('Y'),
                'selectedDari' => $selectedDari,
                'selectedSampai' => $selectedSampai,
            ];
        }

        if ($mode === 'harian') {
            try {
                $dari = Carbon::parse($selectedDate)->startOfDay();
                $sampai = Carbon::parse($selectedDate)->endOfDay();
            } catch (\Throwable $e) {
                $selectedDate = now()->format('Y-m-d');
                $dari = now()->startOfDay();
                $sampai = now()->endOfDay();
            }
        } elseif ($mode === 'tahunan') {
            $selectedYear = max(2000, min(2100, $selectedYear));
            $dari = now()->setYear($selectedYear)->startOfYear();
            $sampai = now()->setYear($selectedYear)->endOfYear();
        } else {
            try {
                $monthBase = Carbon::parse($selectedMonth.'-01');
            } catch (\Throwable $e) {
                $selectedMonth = now()->format('Y-m');
                $monthBase = now()->startOfMonth();
            }
            $dari = $monthBase->copy()->startOfMonth();
            $sampai = $monthBase->copy()->endOfMonth();
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'mode' => $mode,
            'selectedDate' => $selectedDate,
            'selectedMonth' => $selectedMonth,
            'selectedYear' => $selectedYear,
            'selectedDari' => $dari->format('Y-m-d'),
            'selectedSampai' => $sampai->format('Y-m-d'),
        ];
    }

    /**
     * Rentang bandingkan kustom (mode range). Null jika salah satu tanggal kosong/invalid.
     *
     * @return array{dari: Carbon, sampai: Carbon, label: string}|null
     */
    public static function compareRangeFromRequest(Request $request): ?array
    {
        $dariRaw = trim((string) $request->input('compare_dari', ''));
        $sampaiRaw = trim((string) $request->input('compare_sampai', ''));
        if ($dariRaw === '' || $sampaiRaw === '') {
            return null;
        }

        try {
            $dari = Carbon::parse($dariRaw)->startOfDay();
            $sampai = Carbon::parse($sampaiRaw)->endOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        if ($dari->gt($sampai)) {
            [$dari, $sampai] = [$sampai->copy()->startOfDay(), $dari->copy()->endOfDay()];
        }

        return [
            'dari' => $dari,
            'sampai' => $sampai,
            'label' => $dari->translatedFormat('j M').' – '.$sampai->translatedFormat('j M Y'),
        ];
    }

    public static function chartPeriodHint(
        string $mode,
        Carbon $dari,
        Carbon $sampai,
        string $selectedDate,
        string $selectedMonth,
        int $selectedYear,
    ): string {
        return match ($mode) {
            'semua' => 'Semua periode',
            'tahunan' => 'Per bulan pada tahun '.$selectedYear,
            'bulanan' => 'Per hari pada '.(Carbon::parse($selectedMonth.'-01')->format('F Y')),
            'range' => 'Per hari ('.$dari->format('d/m/Y').' – '.$sampai->format('d/m/Y').')',
            default => 'Tanggal '.Carbon::parse($selectedDate)->format('d/m/Y'),
        };
    }
}
