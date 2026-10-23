@php
    $flashSuccess = session('success');
    $flashError = session('error');
    $flashInfo = session('info');
    $flashValidationErrors = isset($errors) ? $errors->all() : [];
    $hasFlashMessage = $flashSuccess || $flashError || $flashInfo
        || (is_array($flashValidationErrors) && count($flashValidationErrors) > 0);
@endphp
@if($hasFlashMessage)
@once
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') return;

    const flashSuccess = @json($flashSuccess);
    const flashError = @json($flashError);
    const flashInfo = @json($flashInfo);
    const validationErrors = @json($flashValidationErrors);

    if (flashError) {
        Swal.fire({ title: 'Gagal', text: String(flashError), icon: 'error', confirmButtonColor: '#8CFA01' });
        return;
    }
    if (Array.isArray(validationErrors) && validationErrors.length > 0) {
        Swal.fire({
            title: 'Periksa input',
            html: `<div style="text-align:left;font-size:14px;">${validationErrors.map((e) => `<div>• ${String(e)}</div>`).join('')}</div>`,
            icon: 'warning',
            confirmButtonColor: '#8CFA01',
        });
        return;
    }
    if (flashInfo) {
        Swal.fire({ title: 'Info', text: String(flashInfo), icon: 'info', confirmButtonColor: '#8CFA01' });
        return;
    }
    if (flashSuccess) {
        Swal.fire({ title: 'Berhasil', text: String(flashSuccess), icon: 'success', confirmButtonColor: '#8CFA01' });
    }
});
</script>
@endpush
@endonce
@endif
