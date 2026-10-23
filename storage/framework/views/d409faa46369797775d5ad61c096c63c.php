<?php
    $woInputClass = 'wo-input wo-native';
    $idPrefix = $idPrefix ?? '';
?>

<div class="space-y-4">
    <?php if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_keuangan_rekenings', 'jenis')): ?>
    <div>
        <label for="<?php echo e($idPrefix); ?>jenis" class="wo-label">Jenis</label>
        <select id="<?php echo e($idPrefix); ?>jenis" name="jenis" required class="<?php echo e($woInputClass); ?>">
            <?php $__currentLoopData = \App\Models\SpeedshopKeuanganRekening::jenisOptions(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <option value="<?php echo e($value); ?>" <?php if(old('jenis', $rekening->jenis ?? \App\Models\SpeedshopKeuanganRekening::JENIS_REKENING_UTAMA) === $value): echo 'selected'; endif; ?>><?php echo e($label); ?></option>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </select>
        <?php $__errorArgs = ['jenis'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <?php endif; ?>
    <div>
        <label for="<?php echo e($idPrefix); ?>nama" class="wo-label">Nama rekening</label>
        <input id="<?php echo e($idPrefix); ?>nama" type="text" name="nama" value="<?php echo e(old('nama', $rekening->nama ?? '')); ?>" required maxlength="160" class="<?php echo e($woInputClass); ?>" placeholder="Contoh: Rekening Operasional">
        <?php $__errorArgs = ['nama'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div>
        <label for="<?php echo e($idPrefix); ?>nama_bank" class="wo-label">Nama bank</label>
        <input id="<?php echo e($idPrefix); ?>nama_bank" type="text" name="nama_bank" value="<?php echo e(old('nama_bank', $rekening->nama_bank ?? '')); ?>" maxlength="120" class="<?php echo e($woInputClass); ?>" placeholder="Contoh: BCA">
        <?php $__errorArgs = ['nama_bank'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <div>
        <label for="<?php echo e($idPrefix); ?>nomor_rekening" class="wo-label">Nomor rekening</label>
        <input id="<?php echo e($idPrefix); ?>nomor_rekening" type="text" name="nomor_rekening" value="<?php echo e(old('nomor_rekening', $rekening->nomor_rekening ?? '')); ?>" required maxlength="64" class="<?php echo e($woInputClass); ?> font-mono tabular-nums" inputmode="numeric" autocomplete="off" placeholder="1234567890">
        <?php $__errorArgs = ['nomor_rekening'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <?php if(\Illuminate\Support\Facades\Schema::hasColumn('speedshop_keuangan_rekenings', 'keterangan')): ?>
    <div>
        <label for="<?php echo e($idPrefix); ?>keterangan" class="wo-label">Keterangan <span class="font-normal text-gray-400">(opsional)</span></label>
        <textarea id="<?php echo e($idPrefix); ?>keterangan" name="keterangan" rows="3" maxlength="5000" class="<?php echo e($woInputClass); ?> min-h-[5.5rem] resize-y py-2.5" placeholder="Catatan tambahan"><?php echo e(old('keterangan', $rekening->keterangan ?? '')); ?></textarea>
        <?php $__errorArgs = ['keterangan'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?><p class="mt-1 text-sm text-red-600"><?php echo e($message); ?></p><?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
    </div>
    <?php endif; ?>
</div>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/speedshop/keuangan/rekening/_form.blade.php ENDPATH**/ ?>