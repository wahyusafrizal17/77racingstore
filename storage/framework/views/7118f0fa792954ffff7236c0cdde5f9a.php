
<script>
window.ChatIncomingSound = window.ChatIncomingSound || (function () {
    const url = <?php echo json_encode(asset('sound/chat-masuk.mp3'), 15, 512) ?>;
    let audio = null;
    let lastAt = 0;
    let ready = false;
    let lastUnread = 0;

    function preload() {
        if (typeof Audio === 'undefined') return;
        try {
            audio = new Audio(url);
            audio.preload = 'auto';
        } catch (e) {}
    }

    function play() {
        if (typeof Audio === 'undefined') return;
        const now = Date.now();
        if (now - lastAt < 1500) return;
        lastAt = now;
        try {
            if (!audio) audio = new Audio(url);
            audio.currentTime = 0;
            void audio.play().catch(function () {});
        } catch (e) {}
    }

    function noteUnread(next) {
        const value = Math.max(0, Number(next) || 0);
        if (ready && value > lastUnread) {
            play();
        }
        lastUnread = value;
        ready = true;
        return value;
    }

    function syncUnread(next) {
        lastUnread = Math.max(0, Number(next) || 0);
        ready = true;
        return lastUnread;
    }

    return {
        url: url,
        preload: preload,
        play: play,
        noteUnread: noteUnread,
        syncUnread: syncUnread,
    };
})();
</script>
<?php /**PATH /Users/wahyusafrizal/work/Github/77racingstore/resources/views/partials/shared/chat-incoming-sound.blade.php ENDPATH**/ ?>