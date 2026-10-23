<?php

namespace App\Http\Controllers\Speedshop;

use App\Http\Controllers\Controller;
use App\Services\InvoiceSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(
        private readonly InvoiceSettingsService $invoiceSettings,
    ) {}

    public function index(Request $request)
    {
        if (! $request->user()?->isManager()) {
            abort(403);
        }

        $invoiceSettings = $this->invoiceSettings->get();

        return view('speedshop.settings.index', compact('invoiceSettings'));
    }

    public function update(Request $request): RedirectResponse
    {
        if (! $request->user()?->isManager()) {
            abort(403);
        }

        $data = $request->validate([
            'invoice_charge_mode' => ['required', 'in:percent,rupiah'],
            'invoice_charge_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_charge_rupiah' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
            'invoice_ppn_mode' => ['required', 'in:percent,rupiah'],
            'invoice_ppn_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'invoice_ppn_rupiah' => ['nullable', 'numeric', 'min:0', 'max:1000000000'],
        ]);

        $this->invoiceSettings->update($data);

        return redirect()
            ->route('speedshop.settings.index')
            ->with('success', 'Pengaturan invoice berhasil disimpan untuk semua speedshop.');
    }

    public function updateStokAudit(Request $request): RedirectResponse
    {
        if (! $request->user()?->isManager()) {
            abort(403);
        }

        $data = $request->validate([
            'stok_audit_percent' => ['required', 'numeric', 'min:0.01', 'max:100'],
        ]);

        $this->invoiceSettings->updateStokAudit($data);

        return redirect()
            ->route('speedshop.settings.index')
            ->with('success', 'Pengaturan audit stok berhasil disimpan untuk semua speedshop.');
    }
}
