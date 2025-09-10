<script>
    (function() {
        const url = @json(route('heartbeat'));
        const INTERVAL_MS = 10 * 60 * 1000; // 10 minutes

        function ping() {
            if (document.hidden) return;
            if ('sendBeacon' in navigator) {
                try {
                    navigator.sendBeacon(url);
                } catch (e) {
                    console.error('sendBeacon heartbeat failed:', e);
                }
            } else {
                fetch(url, { method: 'POST', headers: { 'X-CSRF-TOKEN': @json(csrf_token()) } }).catch(() => {
                });
            }
        }

        setInterval(ping, INTERVAL_MS);
        window.addEventListener('focus', ping);
    })();
</script>

<script>
    document.addEventListener('livewire:init', () => {
        if (window.Livewire && typeof Livewire.onError === 'function') {
            Livewire.onError((status) => {
                if (status === 419) {
                    window.location.reload();
                    return true; // prevent default confirm
                }
            });
        }
    });
</script>

