<?php
    $flashSuccess = session('success');
    $flashError = session('error');
    $flashInfo = session('info');
    $flashValidationErrors = isset($errors) ? $errors->all() : [];
    $hasFlashMessage = $flashSuccess || $flashError || $flashInfo
        || (is_array($flashValidationErrors) && count($flashValidationErrors) > 0);
?>
<?php if($hasFlashMessage): ?>
<?php if (! $__env->hasRenderedOnce('42cfed39-990e-4e08-8bba-28dd80724dfa')): $__env->markAsRenderedOnce('42cfed39-990e-4e08-8bba-28dd80724dfa'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Swal === 'undefined' || typeof Swal.fire !== 'function') return;

    const flashSuccess = <?php echo json_encode($flashSuccess, 15, 512) ?>;
    const flashError = <?php echo json_encode($flashError, 15, 512) ?>;
    const flashInfo = <?php echo json_encode($flashInfo, 15, 512) ?>;
    const validationErrors = <?php echo json_encode($flashValidationErrors, 15, 512) ?>;

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
<?php $__env->stopPush(); ?>
<?php endif; ?>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/flash.blade.php ENDPATH**/ ?>