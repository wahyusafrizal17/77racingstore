@if(auth()->user()?->isModifikasiOriHeadAudit())
@once('finance-audit-readonly-ui')
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (!document.body.classList.contains('finance-audit-view-only')) {
        return;
    }

    var mutatePattern = /(\+\s*)?(tambah|import|simpan|submit|ajukan|verifikasi|approve|tolak|bayar|konfirmasi|hapus|upload|setujui|batalkan)/i;

    document.querySelectorAll('button.btn-primary, a.btn-primary, button.btn-secondary, a.btn-secondary').forEach(function (el) {
        if (el.closest('.finance-audit-allow')) {
            return;
        }
        var text = (el.textContent || '').replace(/\s+/g, ' ').trim();
        if (mutatePattern.test(text) || /import\s*excel/i.test(text)) {
            el.style.setProperty('display', 'none', 'important');
        }
    });

    document.querySelectorAll('.wo-icon-btn, .finance-mutate').forEach(function (el) {
        el.style.setProperty('display', 'none', 'important');
    });

    document.querySelectorAll('form').forEach(function (form) {
        if (form.closest('.finance-audit-allow')) {
            return;
        }
        var methodInput = form.querySelector('input[name="_method"]');
        if (methodInput && ['DELETE', 'PUT', 'PATCH'].includes(String(methodInput.value || '').toUpperCase())) {
            form.remove();
        }
    });
});
</script>
@endpush
@endonce
@endif
