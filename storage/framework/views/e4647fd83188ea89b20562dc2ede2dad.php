<?php if(session('show_birthday_wish') && auth()->check()): ?>
<?php
    $birthdayName = trim((string) (auth()->user()->name ?? 'teman'));
?>
<div id="birthdayWishRoot" class="birthday-wish" role="dialog" aria-modal="true" aria-labelledby="birthdayWishTitle">
    <div class="birthday-wish__backdrop" data-birthday-close></div>

    <div class="birthday-wish__sky" aria-hidden="true">
        <?php $__currentLoopData = [
            ['left' => '6%', 'delay' => '0s', 'dur' => '9s', 'size' => '52px', 'color' => '#ef4444', 'sway' => '12px'],
            ['left' => '14%', 'delay' => '1.2s', 'dur' => '11s', 'size' => '40px', 'color' => '#f59e0b', 'sway' => '-14px'],
            ['left' => '22%', 'delay' => '0.4s', 'dur' => '10s', 'size' => '46px', 'color' => '#0d9488', 'sway' => '10px'],
            ['left' => '32%', 'delay' => '2s', 'dur' => '12s', 'size' => '36px', 'color' => '#e11d48', 'sway' => '-11px'],
            ['left' => '42%', 'delay' => '0.8s', 'dur' => '9.5s', 'size' => '48px', 'color' => '#2563eb', 'sway' => '13px'],
            ['left' => '52%', 'delay' => '1.6s', 'dur' => '11.5s', 'size' => '42px', 'color' => '#f97316', 'sway' => '-12px'],
            ['left' => '62%', 'delay' => '0.2s', 'dur' => '10.5s', 'size' => '50px', 'color' => '#db2777', 'sway' => '11px'],
            ['left' => '72%', 'delay' => '1.8s', 'dur' => '9.8s', 'size' => '38px', 'color' => '#16a34a', 'sway' => '-10px'],
            ['left' => '80%', 'delay' => '0.6s', 'dur' => '12.2s', 'size' => '44px', 'color' => '#dc2626', 'sway' => '14px'],
            ['left' => '88%', 'delay' => '2.4s', 'dur' => '10.2s', 'size' => '34px', 'color' => '#ca8a04', 'sway' => '-13px'],
            ['left' => '10%', 'delay' => '3s', 'dur' => '11s', 'size' => '30px', 'color' => '#0891b2', 'sway' => '9px'],
            ['left' => '48%', 'delay' => '2.8s', 'dur' => '13s', 'size' => '56px', 'color' => '#b91c1c', 'sway' => '-15px'],
            ['left' => '68%', 'delay' => '3.4s', 'dur' => '10.8s', 'size' => '32px', 'color' => '#ea580c', 'sway' => '12px'],
            ['left' => '92%', 'delay' => '1s', 'dur' => '11.8s', 'size' => '46px', 'color' => '#be123c', 'sway' => '-9px'],
        ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <span class="birthday-balloon"
            style="--b-left: <?php echo e($b['left']); ?>; --b-delay: <?php echo e($b['delay']); ?>; --b-dur: <?php echo e($b['dur']); ?>; --b-size: <?php echo e($b['size']); ?>; --b-color: <?php echo e($b['color']); ?>; --b-sway: <?php echo e($b['sway']); ?>;">
            <span class="birthday-balloon__body"></span>
            <span class="birthday-balloon__knot"></span>
            <span class="birthday-balloon__string"></span>
        </span>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>

    <div class="birthday-wish__card">
        <div class="birthday-wish__icon" aria-hidden="true">
            <svg viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect x="12" y="30" width="40" height="22" rx="6" fill="#fca5a5"/>
                <rect x="16" y="22" width="32" height="12" rx="5" fill="#fecaca"/>
                <path d="M20 30c3-4 6-4 9 0 3-4 6-4 9 0 3-4 6-4 9 0" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M32 10v8" stroke="#b45309" stroke-width="2.5" stroke-linecap="round"/>
                <path d="M32 8c2.2 0 3.5 2.2 2.2 4.2-.7 1.1-2.2 1.8-2.2 1.8s-1.5-.7-2.2-1.8C28.5 10.2 29.8 8 32 8z" fill="#f59e0b"/>
                <circle cx="24" cy="40" r="2.2" fill="#dc2626"/>
                <circle cx="32" cy="44" r="2.2" fill="#b91c1c"/>
                <circle cx="40" cy="39" r="2.2" fill="#dc2626"/>
            </svg>
        </div>
        <p class="birthday-wish__eyebrow">Hari spesial</p>
        <h2 id="birthdayWishTitle" class="birthday-wish__title">Selamat ulang tahun!</h2>
        <p class="birthday-wish__name"><?php echo e($birthdayName); ?></p>
        <p class="birthday-wish__message">
            Semoga hari ini penuh kebahagiaan, kesehatan, dan langkah baik di depan.
            Terima kasih sudah jadi bagian dari keluarga 77racing.
        </p>
        <button type="button" class="birthday-wish__btn" data-birthday-close>
            Terima kasih
        </button>
    </div>
</div>

<style>
.birthday-wish {
    position: fixed;
    inset: 0;
    z-index: 99999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1.25rem;
    animation: birthday-fade-in .35s ease-out both;
}
.birthday-wish.is-closing {
    animation: birthday-fade-out .28s ease-in both;
}
.birthday-wish__backdrop {
    position: absolute;
    inset: 0;
    background: radial-gradient(ellipse at 50% 30%, rgba(127, 29, 29, .55), rgba(15, 23, 42, .72));
    backdrop-filter: blur(2px);
}
.birthday-wish__sky {
    position: absolute;
    inset: 0;
    overflow: hidden;
    pointer-events: none;
}
.birthday-balloon {
    position: absolute;
    left: var(--b-left);
    bottom: -20%;
    width: var(--b-size);
    height: calc(var(--b-size) * 1.25);
    animation: birthday-float var(--b-dur) linear var(--b-delay) infinite;
    will-change: transform;
}
.birthday-balloon__body {
    display: block;
    width: 100%;
    height: 78%;
    border-radius: 50% 50% 48% 48%;
    background:
        radial-gradient(circle at 32% 28%, rgba(255,255,255,.55), transparent 28%),
        linear-gradient(160deg, var(--b-color), var(--b-color));
    filter: saturate(1.05);
    box-shadow: inset -6px -10px 16px rgba(0,0,0,.12);
}
.birthday-balloon__knot {
    display: block;
    width: 10px;
    height: 8px;
    margin: -2px auto 0;
    background: var(--b-color);
    clip-path: polygon(50% 100%, 0 0, 100% 0);
    opacity: .95;
}
.birthday-balloon__string {
    display: block;
    width: 1.5px;
    height: 42px;
    margin: 0 auto;
    background: linear-gradient(to bottom, rgba(255,255,255,.55), rgba(255,255,255,.1));
}
.birthday-wish__card {
    position: relative;
    z-index: 2;
    width: min(100%, 420px);
    padding: 2rem 1.75rem 1.75rem;
    border-radius: 24px;
    background: linear-gradient(180deg, #fffdf8 0%, #fff 55%, #fff7ed 100%);
    border: 1px solid rgba(254, 215, 170, .9);
    box-shadow: 0 24px 60px rgba(0,0,0,.28);
    text-align: center;
    animation: birthday-pop .45s cubic-bezier(.22,1.2,.36,1) .1s both;
}
.birthday-wish__icon {
    width: 4.25rem;
    height: 4.25rem;
    margin: 0 auto .65rem;
    filter: drop-shadow(0 6px 10px rgba(220, 38, 38, .18));
    animation: birthday-bounce 1.4s ease-in-out infinite;
}
.birthday-wish__icon svg {
    width: 100%;
    height: 100%;
    display: block;
}
.birthday-wish__eyebrow {
    margin: 0;
    font-size: .72rem;
    font-weight: 700;
    letter-spacing: .14em;
    text-transform: uppercase;
    color: #b45309;
}
.birthday-wish__title {
    margin: .4rem 0 0;
    font-size: clamp(1.55rem, 4vw, 1.9rem);
    font-weight: 800;
    letter-spacing: -.02em;
    color: #7f1d1d;
    line-height: 1.15;
}
.birthday-wish__name {
    margin: .55rem 0 0;
    font-size: 1.05rem;
    font-weight: 700;
    color: #9a3412;
}
.birthday-wish__message {
    margin: .85rem 0 0;
    font-size: .925rem;
    line-height: 1.55;
    color: #57534e;
}
.birthday-wish__btn {
    margin-top: 1.35rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 180px;
    padding: .7rem 1.25rem;
    border: 0;
    border-radius: 999px;
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    color: #fff;
    font-size: .925rem;
    font-weight: 700;
    cursor: pointer;
    box-shadow: 0 10px 22px rgba(185, 28, 28, .28);
    transition: transform .15s ease, filter .15s ease;
}
.birthday-wish__btn:hover {
    filter: brightness(1.05);
    transform: translateY(-1px);
}
.birthday-wish__btn:active {
    transform: translateY(0);
}
@keyframes birthday-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}
@keyframes birthday-fade-out {
    from { opacity: 1; }
    to { opacity: 0; }
}
@keyframes birthday-pop {
    from { opacity: 0; transform: translateY(18px) scale(.92); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}
@keyframes birthday-bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-6px); }
}
@keyframes birthday-float {
    0% {
        transform: translate3d(0, 0, 0) rotate(-4deg);
        opacity: 0;
    }
    8% { opacity: 1; }
    50% {
        transform: translate3d(var(--b-sway), -55vh, 0) rotate(4deg);
    }
    92% { opacity: 1; }
    100% {
        transform: translate3d(calc(var(--b-sway) * -1), -115vh, 0) rotate(-3deg);
        opacity: 0;
    }
}
@media (prefers-reduced-motion: reduce) {
    .birthday-balloon,
    .birthday-wish__icon,
    .birthday-wish__card {
        animation: none !important;
    }
}
</style>

<script>
(function () {
    const root = document.getElementById('birthdayWishRoot');
    if (!root) return;

    const close = () => {
        if (root.classList.contains('is-closing')) return;
        root.classList.add('is-closing');
        window.setTimeout(() => root.remove(), 280);
    };

    root.querySelectorAll('[data-birthday-close]').forEach((el) => {
        el.addEventListener('click', close);
    });

    document.addEventListener('keydown', function onKey(e) {
        if (e.key === 'Escape') {
            document.removeEventListener('keydown', onKey);
            close();
        }
    });
})();
</script>
<?php endif; ?>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/birthday-wish-modal.blade.php ENDPATH**/ ?>