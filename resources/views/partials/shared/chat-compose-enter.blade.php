<script>
window.ChatComposeEnter = window.ChatComposeEnter || {
    shouldSend(event) {
        if (!event || event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
            return false;
        }
        if (event.isComposing || event.keyCode === 229) {
            return false;
        }
        const key = String(event.key || '');
        const code = String(event.code || '');

        return key === 'Enter' || key === 'NumpadEnter' || code === 'Enter' || code === 'NumpadEnter';
    },
    handle(event, send) {
        if (!this.shouldSend(event)) {
            return false;
        }
        event.preventDefault();
        if (typeof send === 'function') {
            send();
        }
        return true;
    },
    bind(input, send) {
        if (!input || input.dataset.chatEnterBound === '1') {
            return;
        }
        input.dataset.chatEnterBound = '1';
        input.addEventListener('keydown', function (event) {
            window.ChatComposeEnter.handle(event, send);
        });
    },
};
</script>
