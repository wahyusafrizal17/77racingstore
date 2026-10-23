<?php
    use App\Support\SpeedshopLabaRugiFinancials;
?>
<?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php
        $kind = $row['kind'] ?? 'line';
        $isFooter = $kind === 'footer';
        $showRp = in_array($kind, ['line', 'subtotal', 'grand', 'footer'], true) && ! ($row['is_percent'] ?? false);
        $amount = $row['amount'] ?? null;
        $indentClass = SpeedshopLabaRugiFinancials::labaRugiRowPaddingClass($row);
        $amountTone = match ($kind) {
            'subtotal', 'grand' => 'text-red-600',
            'footer' => 'text-white',
            default => 'text-gray-900',
        };
        $sectionNo = (string) ($row['no'] ?? '');
        $showSectionSort = ($labaRugiSectionSortEnabled ?? false)
            && $kind === 'sub'
            && isset(($labaRugiSortableSections ?? [])[$sectionNo])
            && isset($labaRugiSortUrl);
        $sectionSort = ($labaRugiSectionSort ?? [])[$sectionNo] ?? null;
    ?>
    <tr class="<?php echo e($rowClass($kind)); ?>">
        <td class="px-5 py-2.5 font-mono text-[13px] tabular-nums <?php echo e($indentClass); ?> <?php echo e($isFooter ? 'text-white/95' : 'text-gray-600'); ?>">
            <?php echo e(($row['no'] ?? '') !== '' ? $row['no'] : ''); ?>

        </td>
        <td class="px-5 py-2.5 <?php echo e($indentClass); ?> <?php echo e($isFooter ? 'text-white' : 'text-gray-800'); ?>">
            <?php echo e($row['label']); ?>

        </td>
        <td class="px-5 py-2.5 text-right font-medium tabular-nums <?php echo e($isFooter ? 'text-white' : ''); ?>">
            <?php if($showSectionSort): ?>
                <div class="inline-flex items-center justify-end gap-0.5">
                    <a
                        href="<?php echo e($labaRugiSortUrl($sectionNo, 'asc')); ?>"
                        class="lr-sort-btn <?php echo e($sectionSort === 'asc' ? 'is-active' : ''); ?>"
                        title="Urutkan terendah ke tertinggi"
                        aria-label="Urutkan <?php echo e($row['label']); ?> terendah ke tertinggi"
                        @click="loading = true"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
                        </svg>
                    </a>
                    <a
                        href="<?php echo e($labaRugiSortUrl($sectionNo, 'desc')); ?>"
                        class="lr-sort-btn <?php echo e($sectionSort === 'desc' ? 'is-active' : ''); ?>"
                        title="Urutkan tertinggi ke terendah"
                        aria-label="Urutkan <?php echo e($row['label']); ?> tertinggi ke terendah"
                        @click="loading = true"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </a>
                </div>
            <?php elseif($amount !== null): ?>
                <?php if($row['is_percent'] ?? false): ?>
                    <span class="<?php echo e($isFooter ? 'text-white' : 'text-gray-900'); ?>"><?php echo e($fmtPct($amount)); ?></span>
                <?php elseif($showRp): ?>
                    <span class="inline-flex w-full items-baseline justify-end gap-2">
                        <span class="<?php echo e($isFooter ? 'text-white/90' : ($amountTone === 'text-red-600' ? 'text-red-600' : 'text-rose-600/80')); ?> text-xs font-medium">Rp</span>
                        <span class="<?php echo e($amountTone); ?>"><?php echo e($fmt($amount)); ?></span>
                    </span>
                <?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/laporan/laba-rugi-table-body.blade.php ENDPATH**/ ?>