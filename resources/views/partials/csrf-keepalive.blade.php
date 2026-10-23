{{-- Keep CSRF + session hidup; hindari "419 Page Expired" saat tab lama / back-forward cache. --}}
<script>
(function () {
    var refreshUrl = @json(route('session.csrf'));
    var lifetimeMinutes = {{ max(30, (int) config('session.lifetime', 120)) }};
    // Refresh sebelum session habis (paling lambat tiap 10 menit).
    var intervalMs = Math.max(60, Math.min(600, Math.floor(lifetimeMinutes * 60 / 3))) * 1000;
    var refreshing = false;

    function applyToken(token) {
        if (!token) return;
        document.querySelectorAll('meta[name="csrf-token"]').forEach(function (meta) {
            meta.setAttribute('content', token);
        });
        document.querySelectorAll('input[name="_token"]').forEach(function (input) {
            input.value = token;
        });
        if (window.axios && axios.defaults && axios.defaults.headers) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
            if (axios.defaults.headers.common['X-XSRF-TOKEN'] !== undefined) {
                // biarkan cookie XSRF tetap dipakai axios bila ada
            }
        }
    }

    function refreshCsrf() {
        if (refreshing || document.hidden) return;
        refreshing = true;
        fetch(refreshUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            cache: 'no-store'
        })
            .then(function (res) {
                if (res.status === 401 || res.status === 419) {
                    return null;
                }
                if (!res.ok) throw new Error('csrf refresh failed');
                return res.json();
            })
            .then(function (data) {
                if (data && data.token) applyToken(data.token);
            })
            .catch(function () { /* diam — coba lagi di interval berikutnya */ })
            .finally(function () { refreshing = false; });
    }

    // Saat kembali dari bfcache / tab fokus lagi, sync token segera.
    window.addEventListener('pageshow', function (e) {
        if (e.persisted) refreshCsrf();
    });
    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) refreshCsrf();
    });

    setInterval(refreshCsrf, intervalMs);
    // Touch awal setelah load (bantu multi-tab).
    setTimeout(refreshCsrf, 15000);
})();
</script>
